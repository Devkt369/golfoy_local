<?php

namespace ArtM\CouponValidation\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\CouponValidation\Model\CsvProcessor;
use ArtM\CouponValidation\Model\CouponAllocationService;
use Magento\Framework\Controller\Result\JsonFactory;

class Save extends Action
{
    const ADMIN_RESOURCE = 'ArtM_CouponValidation::couponvalidation';

    /** @var CsvProcessor */
    protected $csvProcessor;

    /** @var CouponAllocationService */
    protected $allocationService;

    /** @var JsonFactory */
    protected $jsonFactory;

    public function __construct(
        Context $context,
        CsvProcessor $csvProcessor,
        CouponAllocationService $allocationService,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->csvProcessor = $csvProcessor;
        $this->allocationService = $allocationService;
        $this->jsonFactory = $jsonFactory;
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();

        if (!$this->getRequest()->isXmlHttpRequest()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid request')
            ]);
        }

        try {
            $ruleId = (int)$this->getRequest()->getParam('rule_id');
            $expiryDateRaw = $this->getRequest()->getParam('expiry_date');
            $couponCountInput = (int)$this->getRequest()->getParam('coupon_count');

            if (!$ruleId) {
                throw new \Exception(__('Please select a sales rule.'));
            }

            if (!$expiryDateRaw) {
                throw new \Exception(__('Please select expiry date.'));
            }

            // Normalize expiry to MySQL DATETIME
            $expiryDate = str_replace('T', ' ', $expiryDateRaw);
            $expiryDateTs = strtotime($expiryDate);
            if ($expiryDateTs === false) {
                throw new \Exception(__('Invalid expiry date format.'));
            }
            $expiryDate = date('Y-m-d H:i:s', $expiryDateTs);

            $requiredCount = 0;
            $csvRows = [];

            // Priority: input coupon_count if provided
            if ($couponCountInput > 0) {
                $requiredCount = $couponCountInput;
            } else {
                // Check uploaded CSV
                if (!isset($_FILES['csv_file']) || empty($_FILES['csv_file']['tmp_name'])) {
                    throw new \Exception(__('Please enter coupon count or upload a CSV.'));
                }

                $filePath = $_FILES['csv_file']['tmp_name'];
                $rowsNeeding = $this->csvProcessor->getRowsNeedingCoupon($filePath);

                if (empty($rowsNeeding)) {
                    throw new \Exception(__('No rows in CSV require coupons (need_coupon should be TRUE/true/1/yes for rows needing coupons).'));
                }

                $csvRows = $rowsNeeding;
                $requiredCount = count($csvRows);
            }

            // Use allocation service to perform DB operations
            $allocated = $this->allocationService->allocate($ruleId, $requiredCount, $expiryDate);

            return $resultJson->setData([
                'success' => true,
                'allocated' => count($allocated),
                'coupons' => array_values($allocated)
            ]);

        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}