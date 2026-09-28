<?php
namespace ArtM\BulkProduct\Controller\Cart;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
use Magento\Framework\Session\SessionManagerInterface;
use ArtM\CustomProductDesign\Model\UserdataFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;

use function GuzzleHttp\json_encode;

class Add extends Action
{
    protected $resultJsonFactory;
    protected $cart;
    protected $productRepository;
    protected $logger;
    protected $session;
    protected $eventManager;
    protected $userdataFactory;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        Cart $cart,
        ProductRepositoryInterface $productRepository,
        SessionManagerInterface $session,
        UserdataFactory $userdataFactory,
        EventManager $eventManager,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->cart = $cart;
        $this->productRepository = $productRepository;
        $this->session = $session;
        $this->userdataFactory = $userdataFactory;
        $this->logger = $logger;
        $this->eventManager = $eventManager;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $params = $this->getRequest()->getParams();

        if (empty($params['items']) || !is_array($params['items'])) {
            return $resultJson->setData(['success' => false, 'message' => 'No items to add']);
        }

        // try {
            $items = $params['items'];
            $sessionId = $this->session->getSessionId();

            // Group items by configurable product ID
            $groupedItems = [];
            foreach ($items as $item) {
                $groupedItems[$item['configurable_product_id']][] = $item;
            }

            $usedUserdataItems = []; // Keep track of data we use to update it later
            $i = 1;
            foreach ($groupedItems as $configurableId => $simpleItems) {
                

                $configurableProduct = $this->productRepository->getById($configurableId);

                // Fetch user data for THIS specific configurable product
                $userSessionDataCollection = $this->userdataFactory->create()->getCollection()
                    ->addFieldToFilter('product_id', $configurableId)
                    ->addFieldToFilter('session_id', $sessionId)
                    ->addFieldToFilter('attached_to_cart', 0);

                $customAddPrice = 0;
                $userdataArr = [];
                $userdataJson = '[]';

                // Process user data for this configurable product
                foreach ($userSessionDataCollection as $userdataItem) {
                    $additional = json_decode($userdataItem->getData('additional_data'), true);
                    if (!empty($additional['total_price'])) {
                        $customAddPrice += (float)$additional['total_price'];
                    }
                    $userdataArr[] = $userdataItem->getData();
                    $usedUserdataItems[] = $userdataItem; // Mark this item to be updated
                }
                $userdataJson = json_encode($userdataArr);
                foreach ($simpleItems as $item) {
                    // echo $i;
                    
                    $simpleId = (int)$item['product_id'];
                    $qty = (int)$item['qty'];
                    $optionId = $item['option_product_id'];

                    $simpleProduct = $this->productRepository->getById($simpleId);
                    $superAttribute = $this->getSuperAttributeArray($configurableProduct, $simpleProduct);

                    $paramsToAdd = [
                        'product' => $configurableProduct->getId(),
                        'super_attribute' => $superAttribute,
                        'qty' => $qty
                    ];

                    if ($optionId && !empty($userdataArr)) {
                        $paramsToAdd['options'] = [
                            $optionId => $userdataJson
                        ];
                    }
                    // if($i==1){
                    //     // print_r($item);
                    //     print_r($paramsToAdd);die('werwer');
                    // }
                    // $i++;
                    // continue;

                    $this->logger->error(json_encode($item));
                    $this->logger->error(json_encode( $optionId));
                    $this->logger->error(json_encode($userdataJson));




                    $productToAdd = clone $configurableProduct;
                    $this->logger->error(json_encode($paramsToAdd));
                    $this->logger->error(json_encode($productToAdd));
                    $quoteItem = $this->cart->addProduct($productToAdd, $paramsToAdd);
                    
                    if ($quoteItem && $quoteItem->getProduct() && $quoteItem->getProduct()->getId()) {
                        if ($customAddPrice > 0) {
                            $basePrice = $quoteItem->getPrice();
                            $finalPrice = $basePrice + $customAddPrice;

                            $quoteItem->setCustomPrice($finalPrice);
                            $quoteItem->setOriginalCustomPrice($finalPrice);
                            $quoteItem->getProduct()->setIsSuperMode(true);
                        }
                        if (!empty($userdataArr)) {
                            $quoteItem->setData('custom_design_userdata_ids', $userdataJson);
                        }
                    } else {
                        $this->logger->error(
                            'Failed to get a valid quote item after adding product. SKU: ' . $productToAdd->getSku()
                        );
                    }
                    $i++;
                }
            }

            $this->cart->save();

            // Mark used user data as attached
            foreach ($usedUserdataItems as $itemToUpdate) {
                $itemToUpdate->setAttachedToCart(1)->save();
            }

            
            return $resultJson->setData([
                'success' => true,
                'message' => __('Products added to cart successfully.')
            ]);

        // } catch (LocalizedException $e) {
        //     $this->logger->error($e->getMessage());
        //     return $resultJson->setData(['success' => false, 'message' => $e->getMessage()]);
        // } catch (\Exception $e) {
        //     $this->logger->critical($e);
        //     return $resultJson->setData(['success' => false, 'message' => 'An unexpected error occurred.']);
        // }
    }

    protected function getSuperAttributeArray($configurableProduct, $simpleProduct)
    {
        $superAttribute = [];
        $typeInstance = $configurableProduct->getTypeInstance();
        $attributes = $typeInstance->getConfigurableAttributes($configurableProduct);

        foreach ($attributes as $attribute) {
            $attrCode = $attribute->getProductAttribute()->getAttributeCode();
            $attrId = $attribute->getProductAttribute()->getId();
            $attrValue = $simpleProduct->getData($attrCode);
            $superAttribute[$attrId] = $attrValue;
        }
        return $superAttribute;
    }
}