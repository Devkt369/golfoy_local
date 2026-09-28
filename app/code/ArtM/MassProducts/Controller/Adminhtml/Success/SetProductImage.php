<?php

declare(strict_types=1);

namespace ArtM\MassProducts\Controller\Adminhtml\Success;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\MassProducts\Model\ProductImageManagement;

class SetProductImage extends Action
{
    protected $resultJsonFactory;
    protected $imageManagement;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductImageManagement $imageManagement
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->imageManagement = $imageManagement;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        
        // Get data from POST
        $sku = $this->getRequest()->getParam('sku');
        $imageNames = $this->getRequest()->getParam('imageNames');

        if (!$sku || empty($imageNames)) {
            return $result->setData([
                'error' => true, 
                'message' => __('Missing SKU or Image Data.')
            ]);
        }

        try {
            // Call the function you provided in your Model
            $response = $this->imageManagement->setProductImage($sku, $imageNames);
            
            return $result->setData([
                'success' => true,
                'message' => __('Images assigned successfully'),
                'data' => $response->getData() // Assuming your response object has data
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}