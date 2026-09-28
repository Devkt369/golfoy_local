<?php
namespace ArtM\CustomProductDesign\Controller\Userdata;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use ArtM\CustomProductDesign\Model\ResourceModel\Userdata\CollectionFactory;

class Gallery extends Action
{
    protected $collectionFactory;

    public function __construct(Context $context, CollectionFactory $collectionFactory)
    {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $result = ['images' => []];
        $sessionId = $this->getRequest()->getParam('session_id');
        $customerId = $this->getRequest()->getParam('customer_id');
        $productId = $this->getRequest()->getParam('product_id');
        $attachedToCart = $this->getRequest()->getParam('attached_to_cart');
        
        $collection = $this->collectionFactory->create();
        
        // Filter by customer or session
        if ($customerId) {
            $collection->addFieldToFilter('customer_id', $customerId);
        } else if ($sessionId) {
            $collection->addFieldToFilter('session_id', $sessionId);
        }
        
        // Filter by product
        if ($productId) {
            $collection->addFieldToFilter('product_id', $productId);
        }
        
        // Handle attached_to_cart filtering
        if ($attachedToCart !== null && $attachedToCart == '1') {
            // If explicitly requesting attached_to_cart=1, return only those records
            $collection->addFieldToFilter('attached_to_cart', 1);
        } else {
            // By default, exclude records where attached_to_cart=1
            $collection->addFieldToFilter('attached_to_cart', ['neq' => 1]);
        }
        foreach ($collection as $item) {
            $row = [
                'id' => $item->getId(),
                'product_id' => $item->getData('product_id'),
                'customer_id' => $item->getData('customer_id'),
                'session_id' => $item->getData('session_id'),
                'image' => $item->getData('image'),
                'config' => $item->getData('config'),
                'img_path' => $item->getData('img_path'),
                'additional_data' => $item->getData('additional_data'),
                'created_at' => $item->getData('created_at'),
                'updated_at' => $item->getData('updated_at'),
                'attached_to_cart' => $item->getData('attached_to_cart')
            ];
            $result['images'][] = $row;
        }
        return $this->resultFactory->create(ResultFactory::TYPE_JSON)->setData($result);
    }
}
