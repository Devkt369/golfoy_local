<?php
namespace ArtM\EstimateDelivery\Controller\EstimateDelivery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use ArtM\EstimateDelivery\Helper\Data;

class PopulateCache extends Action
{
    protected $jsonFactory;
    protected $helper;
    protected $formKeyValidator;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        FormKeyValidator $formKeyValidator,
        Data $helper
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->formKeyValidator = $formKeyValidator;
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

        $productId = $this->getRequest()->getParam('product_id');

        if (!$productId) {
            return $result->setData([
                'error' => true,
                'message' => 'Product ID is required'
            ]);
        }

        try {
            // Check if cache exists and is valid
            $cachedData = $this->helper->getCachedDeliveryData($productId);
            
            if ($cachedData) {
                // Cache exists and is valid
                return $result->setData([
                    'success' => true,
                    'message' => 'Using cached data',
                    'data' => $cachedData,
                    'cached' => true
                ]);
            }

            // Calculate and cache new data
            $calculatedData = $this->helper->calculateAndCacheDelivery($productId);
            
            if ($calculatedData) {
                return $result->setData([
                    'success' => true,
                    'message' => 'Data calculated and cached',
                    'data' => $calculatedData,
                    'cached' => false
                ]);
            } else {
                return $result->setData([
                    'error' => true,
                    'message' => 'Unable to calculate delivery estimation'
                ]);
            }

        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => 'Calculation failed: ' . $e->getMessage()
            ]);
        }
    }
}