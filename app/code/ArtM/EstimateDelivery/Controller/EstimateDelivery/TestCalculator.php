<?php
namespace ArtM\EstimateDelivery\Controller\EstimateDelivery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use ArtM\EstimateDelivery\Model\EstimateCalculator;
use ArtM\EstimateDelivery\Helper\Data;
use Psr\Log\LoggerInterface;

class TestCalculator extends Action
{
    protected $jsonFactory;
    protected $estimateCalculator;
    protected $helper;
    protected $logger;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        EstimateCalculator $estimateCalculator,
        Data $helper,
        LoggerInterface $logger
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->estimateCalculator = $estimateCalculator;
        $this->helper = $helper;
        $this->logger = $logger;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->jsonFactory->create();

        if (!$this->helper->IsEnabled()) {
            return $result->setData([
                'error' => true,
                'message' => 'EstimateDelivery module is disabled'
            ]);
        }

        // Get product_ids from URL parameter
        // Example: /estimatedelivery/estimatedelivery/testcalculator?product_ids=123,456,789
        $productIdsParam = $this->getRequest()->getParam('product_ids');

        if (!$productIdsParam) {
            return $result->setData([
                'error' => true,
                'message' => 'Missing product_ids parameter',
                'example' => '/estimatedelivery/estimatedelivery/testcalculator?product_ids=123,456,789'
            ]);
        }

        try {
            // Convert comma-separated string to array
            if (is_string($productIdsParam)) {
                $productIds = array_map('intval', explode(',', $productIdsParam));
            } elseif (is_array($productIdsParam)) {
                $productIds = array_map('intval', $productIdsParam);
            } else {
                throw new \Exception('Invalid product_ids format');
            }

            // Remove empty values
            $productIds = array_filter($productIds);

            if (empty($productIds)) {
                return $result->setData([
                    'error' => true,
                    'message' => 'No valid product IDs provided',
                    'example' => '/estimatedelivery/estimatedelivery/testcalculator?product_ids=123,456,789'
                ]);
            }

            $this->logger->info('TestCalculator: Testing with product IDs: ' . implode(', ', $productIds));

            // Call the EstimateCalculator model
            $startTime = microtime(true);
            $dates = $this->estimateCalculator->getEstimatedDates($productIds);
            $executionTime = round((microtime(true) - $startTime) * 1000, 2);

            if (empty($dates)) {
                return $result->setData([
                    'error' => true,
                    'message' => 'No delivery dates calculated. Check if products exist and are physical products (simple/configurable).',
                    'product_ids_tested' => $productIds,
                    'execution_time_ms' => $executionTime
                ]);
            }

            return $result->setData([
                'success' => true,
                'product_ids' => $productIds,
                'results' => $dates,
                'execution_time_ms' => $executionTime,
                'current_date' => date('Y-m-d'),
                'explanation' => [
                    'total_days_source' => 'Read from artm_delivery_estimation_cache.total_days column',
                    'estimated_delivery_early' => 'EARLIEST delivery date (minimum total_days among all products)',
                    'estimated_delivery_last' => 'LATEST delivery date (maximum total_days among all products)',
                    'use_case' => 'Show customer: "Arrives between EARLY and LAST dates"',
                    'calculation' => 'current_date + total_days'
                ]
            ]);

        } catch (\Exception $e) {
            $this->logger->error('TestCalculator failed: ' . $e->getMessage());
            return $result->setData([
                'error' => true,
                'message' => 'Calculation failed: ' . $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}
