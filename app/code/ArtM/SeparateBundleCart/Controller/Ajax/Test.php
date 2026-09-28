<?php
/**
 * Simple test controller to verify routing
 */

namespace ArtM\SeparateBundleCart\Controller\Ajax;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;

class Test extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        return $result->setData([
            'success' => true,
            'message' => 'Test controller is working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}