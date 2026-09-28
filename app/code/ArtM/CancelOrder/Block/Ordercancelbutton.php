<?php

namespace ArtM\CancelOrder\Block;

use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\OrderFactory;
use ArtM\CancelOrder\Controller\Page\Cancelorderrequest;
use ArtM\CancelOrder\Model\ResourceModel\CancelOrderCustom\CollectionFactory;

class Ordercancelbutton extends \Magento\Framework\View\Element\Template
{

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;
    protected $orderFactory;
    protected $refundType;
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \ArtM\CancelOrder\Model\BankAccountDetailsFactory $refundType,
        \ArtM\CancelOrder\Model\CancelOrderCustomFactory $CancelOrderCustom,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerRepository = $customerRepository;
        $this->coreRegistry = $registry;
        $this->orderFactory = $orderFactory;
        $this->refundType = $refundType;
        $this->CancelOrderCustom = $CancelOrderCustom;
    }



    function customorder()
    {
        echo  "hello order";
    }

    public function getOrder()
    {
        if ($this->hasData('order')) {
            return $this->getData('order');
        }

        return $this->coreRegistry->registry('current_order');
    }

    public function getOrderStatusLabel($orderId)
    {
        return $order = $this->orderFactory->create()->load($orderId)->getStatusLabel();
        // return $order = $this->orderFactory->create()->load($orderId)->getState();

    }
    public function getOrderStateLabel($orderId)
    {
        //return $order = $this->orderFactory->create()->load($orderId)->getStatusLabel();
        return $order = $this->orderFactory->create()->load($orderId)->getState();
    }
    public function CheckReturnableProduct($orderId)
    {
        return true;
    }

    public function getItemImage($productId)
    {
        try {
            $_product = $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            return 'product not found';
        }
        $image_url = $this->imageHelper->init($_product, 'product_base_image')->getUrl();
        return $image_url;
    }
    //////////////////////////////////////////////////////////////////////////
    public function getAccounts($custId, $method)
    {
        $collection = $this->refundType->create()->getCollection()->addFieldToFilter('customer_id', $custId)->addFieldToFilter('method', $method);

        if ($collection->count() > 0) {
            return $collection;
        }
        return [];
    }


    public function ReplacementProductGrid($order_id, $prd_id)
    {
        if ($this->IsExistReplacementProduct($order_id, $prd_id) == true) {

            $CancelOrderCustomobj = $this->CancelOrderCustom->create();
            $CancelOrderCustomcollection = $CancelOrderCustomobj
                ->getCollection()
                ->addFieldToFilter('order_id', $order_id)
                ->addFieldToFilter('product_ids', $prd_id);
            return $CancelOrderCustomcollection;
        }
        return false;
    }
    public function StatusReplacementProduct($order_id, $prd_id)
    {
        if ($this->IsExistReplacementProduct($order_id, $prd_id) == true) {

            $CancelOrderCustomobj = $this->CancelOrderCustom->create();
            $CancelOrderCustomcollection = $CancelOrderCustomobj
                ->getCollection()
                ->addFieldToFilter('order_id', $order_id)
                ->addFieldToFilter('product_ids', $prd_id);
            foreach ($CancelOrderCustomcollection as $CancelOrderCustom) {
                return $CancelOrderCustom['current_status'];
            }
        }
        return false;
    }
    public function IsExistReplacementProduct($order_id, $prd_id)
    {

        $CancelOrderCustomobj = $this->CancelOrderCustom->create();
        $CancelOrderCustomcollection = $CancelOrderCustomobj
            ->getCollection()
            ->addFieldToFilter('order_id', $order_id)
            ->addFieldToFilter('product_ids', $prd_id);

        if ($CancelOrderCustomcollection->count() == 0) {
            return false;
        } else {
            return true;
        }
    }

    public function returnExchangedisable($_item)
    {
        $productOptions = $_item->getProductOptions();

        if (isset($productOptions['info_buyRequest']['options'])) {
            $options = $productOptions['info_buyRequest']['options'];

            foreach ($options as $optionValue) {
                if (is_string($optionValue) && strlen($optionValue) > 0) {
                    // Match JSON objects inside the string using regex
                    preg_match_all('/\{.*?\}/', $optionValue, $matches);

                    if (!empty($matches[0])) {
                        foreach ($matches[0] as $jsonString) {
                            $decoded = json_decode($jsonString, true);

                            if (
                                is_array($decoded) &&
                                isset($decoded['product_id']) &&
                                isset($decoded['session_id'])
                            ) {
                                // Found an option with product_id and session_id
                                return false;
                            }
                        }
                    } else {
                        // If there's a non-empty value but no JSON structure, assume it's valid custom option
                        return false;
                    }
                }
            }
        }

        return true;
    }

    public function getItem()
    {
        return $this->getData('item');
    }

    public function getReturnExchangeStatus($orderId, $productId)
    {
        $collection = $this->CancelOrderCustom->create()
            ->getCollection()
            ->addFieldToFilter('order_id', $orderId)
            ->addFieldToFilter('product_ids', ['like' => '%' . $productId . '%'])
            ->setOrder('id', 'DESC');

        if ($collection->getSize()) {

            $item = $collection->getFirstItem();

            return [
                'status' => $item->getReturnCancelStatus(),
                'current_status' => $item->getCurrentStatus()
            ];
        }

        return false;
    }
}
