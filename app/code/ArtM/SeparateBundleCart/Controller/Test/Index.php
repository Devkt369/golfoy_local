<?php
/**
 * Test Controller for ArtM SeparateBundleCart
 */

namespace ArtM\SeparateBundleCart\Controller\Test;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Psr\Log\LoggerInterface;

class Index extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $this->logger->info('ArtM SeparateBundleCart: Test controller accessed');
        
        $result = $this->jsonFactory->create();
        return $result->setData([
            'status' => 'success',
            'message' => 'ArtM SeparateBundleCart module is loaded and working!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}