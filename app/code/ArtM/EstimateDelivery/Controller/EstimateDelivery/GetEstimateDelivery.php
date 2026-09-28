<?php
namespace ArtM\EstimateDelivery\Controller\EstimateDelivery;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use ArtM\EstimateDelivery\Helper\Data;

class GetEstimateDelivery extends Action
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

        // if (!$this->formKeyValidator->validate($this->getRequest())) {
        //     return $result->setData([
        //         'error' => true,
        //         'message' => 'Invalid form key'
        //     ]);
        // }

        $storeId = $this->getRequest()->getParam('store_id');
        $storeId = $storeId !== null ? (int)$storeId : null;

        if(!$this->helper->IsEnabled($storeId)) {
            return $result->setData([
                'error' => true,
                'message' => 'Module is disabled'
            ]);
        }

        $sku = $this->getRequest()->getParam('sku');
        $productId = $this->getRequest()->getParam('product_id');

        if (!$sku && !$productId) {
            return $result->setData([
                'error' => true,
                'message' => 'SKU or Product ID is required'
            ]);
        }

        try {
            // Try cache first if product_id provided
            if ($productId) {
                $cachedData = $this->helper->getCachedDeliveryData($productId, $storeId);
                if ($cachedData) {
                    // Calculate delivery date from total_days
                    $deliveryDate = new \DateTime();
                    $deliveryDate->modify("+{$cachedData['total_days']} days");
                    
                    return $result->setData([
                        'dispatch_days' => $cachedData['dispatch_days'],
                        'delivery_days' => $cachedData['delivery_days'],
                        'total_days' => $cachedData['total_days'],
                        'estimated_delivery_date' => $deliveryDate->format('Y-m-d'),
                        'express_shipping' => $cachedData['express_shipping'],
                        'show_estimation' => $cachedData['show_estimation'] ?? true,
                        'cached' => true
                    ]);
                }

                $calculatedData = $this->helper->calculateAndCacheDelivery($productId);
                if ($calculatedData) {
                    $deliveryDate = new \DateTime();
                    $deliveryDate->modify("+{$calculatedData['total_days']} days");

                    return $result->setData([
                        'dispatch_days' => $calculatedData['dispatch_days'],
                        'delivery_days' => $calculatedData['delivery_days'],
                        'total_days' => $calculatedData['total_days'],
                        'estimated_delivery_date' => $deliveryDate->format('Y-m-d'),
                        'express_shipping' => $calculatedData['express_shipping'],
                        'show_estimation' => $calculatedData['show_estimation'] ?? true,
                        'cached' => false
                    ]);
                }
            }

            // Fallback to original calculation
            $deliveryData = $this->helper->getEstimatedDeliveryDate($sku, $storeId);
            $deliveryData['cached'] = false;
            
            return $result->setData($deliveryData);
            
        } catch (\Exception $e) {
            return $result->setData([
                'error' => true,
                'message' => 'Calculation failed: ' . $e->getMessage()
            ]);
        }
    }
}
