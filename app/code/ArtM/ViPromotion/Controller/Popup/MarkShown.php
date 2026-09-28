<?php

namespace ArtM\ViPromotion\Controller\Popup;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\ViPromotion\Helper\Data;

class MarkShown extends Action
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Data $helper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
    }

    /**
     * Mark popup as shown for today
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            // Mark popup as shown for today
            $this->helper->markPopupAsShown();
            
            return $result->setData([
                'success' => true,
                'message' => 'Popup marked as shown'
            ]);
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}