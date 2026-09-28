<?php
namespace ArtM\EstimateDelivery\Controller\EstimateDelivery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use ArtM\EstimateDelivery\Helper\Data;
use ArtM\EstimateDelivery\Model\BatchDeliveryService;
use Psr\Log\LoggerInterface;

class GetBatchDelivery extends Action
{
    protected $jsonFactory;
    protected $helper;
    protected $formKeyValidator;
    protected $batchDeliveryService;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        FormKeyValidator $formKeyValidator,
        Data $helper,
        BatchDeliveryService $batchDeliveryService,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->formKeyValidator = $formKeyValidator;
        $this->batchDeliveryService = $batchDeliveryService;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->formKeyValidator->validate($this->getRequest())) {
            return $result->setData([
                'error' => true,
                'message' => 'Invalid form key'
            ]);
        }

        if (!$this->helper->IsEnabled()) {
            return $result->setData([
                'error' => true,
                'message' => 'Module is disabled'
            ]);
        }

        $productIds = $this->getRequest()->getParam('product_ids');

        if (!$productIds || !is_array($productIds)) {
            return $result->setData([
                'error' => true,
                'message' => 'Product IDs array is required'
            ]);
        }

        try {
            $results = $this->batchDeliveryService->getBatchDelivery($productIds);

            return $result->setData([
                'success' => true,
                'results' => $results
            ]);

        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => 'Batch calculation failed: ' . $e->getMessage()
            ]);
        }
    }
}