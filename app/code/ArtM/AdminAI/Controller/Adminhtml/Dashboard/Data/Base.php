<?php
namespace ArtM\AdminAI\Controller\Adminhtml\Dashboard\Data;

use ArtM\AdminAI\Api\Service\AnalyticsCacheServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

abstract class Base extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Magento_Backend::system';

    /** @var JsonFactory */
    protected $resultJsonFactory;

    /** @var AnalyticsCacheServiceInterface */
    protected $analyticsCacheService;

    /** @var ResourceConnection */
    protected $resource;

    /** @var LoggerInterface */
    protected $logger;

    /** @var StoreManagerInterface */
    protected $storeManager;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        AnalyticsCacheServiceInterface $analyticsCacheService,
        ResourceConnection $resource,
        LoggerInterface $logger,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->analyticsCacheService = $analyticsCacheService;
        $this->resource = $resource;
        $this->logger = $logger;
        $this->storeManager = $storeManager;
    }

    public function execute(): Json
    {
        $filters = $this->resolveFilters();
        $intent = $this->getIntentCode();

        try {
            $cached = $this->analyticsCacheService->getCachedResult($intent, $filters);
            if (is_array($cached)) {
                $cached['meta'] = [
                    'cached' => true,
                    'filters' => $filters,
                ];
                return $this->resultJsonFactory->create()->setData($cached);
            }

            $payload = $this->buildPayload($filters);
            if (!isset($payload['summary']) || !is_array($payload['summary'])) {
                $payload['summary'] = [];
            }
            if (!isset($payload['grid']) || !is_array($payload['grid'])) {
                $payload['grid'] = [];
            }
            if (!isset($payload['charts']) || !is_array($payload['charts'])) {
                $payload['charts'] = [];
            }

            $this->analyticsCacheService->saveCache($intent, $filters, $payload);
            $payload['meta'] = [
                'cached' => false,
                'filters' => $filters,
            ];

            return $this->resultJsonFactory->create()->setData($payload);
        } catch (\Throwable $e) {
            $this->logger->error('Dashboard data controller failed', [
                'intent' => $intent,
                'filters' => $filters,
                'exception' => $e,
            ]);

            return $this->resultJsonFactory->create()->setData([
                'summary' => [],
                'grid' => [],
                'charts' => [],
                'meta' => [
                    'cached' => false,
                    'filters' => $filters,
                    'error' => true,
                ],
            ]);
        }
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    abstract protected function buildPayload(array $filters): array;

    abstract protected function getIntentCode(): string;

    /**
     * @return array<string,mixed>
     */
    protected function resolveFilters(): array
    {
        $range = (string)$this->getRequest()->getParam('range', '30d');
        if (!in_array($range, ['7d', '30d', '90d', 'custom'], true)) {
            $range = '30d';
        }

        $fromDate = $this->normalizeDate((string)$this->getRequest()->getParam('from_date', ''));
        $toDate = $this->normalizeDate((string)$this->getRequest()->getParam('to_date', ''));

        if ($range !== 'custom') {
            $toDate = gmdate('Y-m-d');
            $daysMap = ['7d' => 6, '30d' => 29, '90d' => 89];
            $offset = $daysMap[$range] ?? 29;
            $fromTs = strtotime('-' . $offset . ' days', strtotime($toDate));
            $fromDate = $fromTs !== false ? gmdate('Y-m-d', $fromTs) : $fromDate;
        }

        if ($toDate === null) {
            $toDate = gmdate('Y-m-d');
        }
        if ($fromDate === null) {
            $fromDate = gmdate('Y-m-d', strtotime('-29 days', strtotime($toDate)) ?: time());
        }

        if (strtotime($fromDate) > strtotime($toDate)) {
            $tmp = $fromDate;
            $fromDate = $toDate;
            $toDate = $tmp;
        }

        $storeId = $this->resolveCurrentStoreId();

        $segmentCode = strtoupper(trim((string)$this->getRequest()->getParam('segment_code', '')));
        if ($segmentCode === '') {
            $segmentCode = null;
        }

        $stockProjectionDays = (int)$this->getRequest()->getParam('stock_projection_days', 90);
        if (!in_array($stockProjectionDays, [90, 180, 360], true)) {
            $stockProjectionDays = 90;
        }

        $skuTrackerSku = strtoupper(trim((string)$this->getRequest()->getParam('sku_tracker_sku', '')));
        if ($skuTrackerSku === '') {
            $skuTrackerSku = null;
        }

        $skuTrackerFromDate = $this->normalizeDate((string)$this->getRequest()->getParam('sku_tracker_from_date', ''));
        $skuTrackerToDate = $this->normalizeDate((string)$this->getRequest()->getParam('sku_tracker_to_date', ''));
        if ($skuTrackerFromDate !== null && $skuTrackerToDate !== null && strtotime($skuTrackerFromDate) > strtotime($skuTrackerToDate)) {
            $tmp = $skuTrackerFromDate;
            $skuTrackerFromDate = $skuTrackerToDate;
            $skuTrackerToDate = $tmp;
        }

        $customerPeriod = strtolower(trim((string)$this->getRequest()->getParam('customer_period', '90d')));
        if (!in_array($customerPeriod, ['90d', '180d', '360d', '2y', '3y', '4y', '5y'], true)) {
            $customerPeriod = '90d';
        }

        $customerLookupQuery = trim((string)$this->getRequest()->getParam('customer_lookup_query', ''));
        if ($customerLookupQuery === '') {
            $customerLookupQuery = null;
        }

        return [
            'range' => $range,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'store_id' => $storeId,
            'segment_code' => $segmentCode,
            'stock_projection_days' => $stockProjectionDays,
            'sku_tracker_sku' => $skuTrackerSku,
            'sku_tracker_from_date' => $skuTrackerFromDate,
            'sku_tracker_to_date' => $skuTrackerToDate,
            'customer_period' => $customerPeriod,
            'customer_lookup_query' => $customerLookupQuery,
        ];
    }

    protected function normalizeDate(string $value): ?string
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        $ts = strtotime($value . ' 00:00:00');
        if ($ts === false) {
            return null;
        }

        return gmdate('Y-m-d', $ts);
    }

    protected function resolveCurrentStoreId(): ?int
    {
        $storeIdParam = trim((string)$this->getRequest()->getParam('store_id', ''));
        if ($storeIdParam !== '') {
            $explicitStoreId = max(0, (int)$storeIdParam);
            return $explicitStoreId > 0 ? $explicitStoreId : null;
        }

        $requestStore = trim((string)$this->getRequest()->getParam('store', ''));
        if ($requestStore !== '') {
            if (ctype_digit($requestStore)) {
                $requestStoreId = (int)$requestStore;
                if ($requestStoreId > 0) {
                    return $requestStoreId;
                }
            }

            try {
                $requestStoreId = (int)$this->storeManager->getStore($requestStore)->getId();
                if ($requestStoreId > 0) {
                    return $requestStoreId;
                }
            } catch (\Throwable $e) {
                // Ignore and continue with fallback resolution.
            }
        }

        try {
            $currentStoreId = (int)$this->storeManager->getStore()->getId();
            if ($currentStoreId > 0) {
                return $currentStoreId;
            }
        } catch (\Throwable $e) {
            // Ignore and continue to default store fallback.
        }

        try {
            $defaultStore = $this->storeManager->getDefaultStoreView();
            if ($defaultStore) {
                $defaultStoreId = (int)$defaultStore->getId();
                if ($defaultStoreId > 0) {
                    return $defaultStoreId;
                }
            }
        } catch (\Throwable $e) {
            // Ignore and fall back to null.
        }

        return null;
    }
}