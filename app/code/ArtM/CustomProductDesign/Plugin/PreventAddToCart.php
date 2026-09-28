<?php

namespace ArtM\CustomProductDesign\Plugin;

use Magento\Checkout\Model\Cart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use ArtM\CustomProductDesign\Model\UserdataFactory;
use Magento\Customer\Model\Session as CustomerSession;

class PreventAddToCart
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;
    protected $userdataFactory;
    protected $session;
    protected $customerSession;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        UserdataFactory $userdataFactory,
        CustomerSession $customerSession
    ) {
        $this->productRepository = $productRepository;
        $this->userdataFactory = $userdataFactory;
        $this->customerSession = $customerSession;
    }

    /**
     * Plugin to inject a value for the "Customization" product option before adding to cart.
     */
    public function beforeAddProduct(Cart $subject, $productInfo, $requestInfo = null)
    {
        if (!is_array($requestInfo) || !isset($requestInfo['product'])) {
            return [$productInfo, $requestInfo];
        }
        $json = "";
        try {
            $productId = $requestInfo['product'];
            $product = $this->productRepository->getById($productId);

            $sessionId = $this->customerSession->getSessionId();
            $collection = $this->userdataFactory->create()->getCollection()
                ->addFieldToFilter('product_id', $productId)
                ->addFieldToFilter('session_id', $sessionId)
                ->addFieldToFilter('attached_to_cart', 0);

             if ($collection->getSize() > 0) {
                    foreach ($collection as $userdataItem) {
                        $data = [
                            'id'              => $userdataItem->getData('id'),
                            'product_id'      => $userdataItem->getData('product_id'),
                            // 'customer_id'     => $userdataItem->getData('customer_id'),
                            'session_id'      => $userdataItem->getData('session_id'),
                            // 'img_path'        => $userdataItem->getData('img_path'),
                            // 'additional_data' => $userdataItem->getData('additional_data'),
                        ];

                        $json .= json_encode($data);
                    }
                                
             }

        } catch (\Exception $e) {
            return [$productInfo, $requestInfo];
        }

        if (!isset($requestInfo['options']) || !is_array($requestInfo['options'])) {
            $requestInfo['options'] = [];
        }

        $customText = $json;

        foreach ($product->getOptions() as $option) {
            if (stripos($option->getTitle(), 'Customization') !== false) {
                $optionId = $option->getOptionId();
                $requestInfo['options'][$optionId] = $customText;
            }
        }

        return [$productInfo, $requestInfo];
    }
}
