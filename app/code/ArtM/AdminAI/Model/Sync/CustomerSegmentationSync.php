<?php
namespace ArtM\AdminAI\Model\Sync;

use ArtM\AdminAI\Api\SyncStateServiceInterface;
use ArtM\AdminAI\Model\Service\CustomerSegmentationService;
use Magento\Framework\App\ResourceConnection;
use Psr\Log\LoggerInterface;

class CustomerSegmentationSync
{
    private const SYNC_CODE = 'customer_segmentation';

    /** @var ResourceConnection */
    private $resource;

    /** @var SyncStateServiceInterface */
    private $syncStateService;

    /** @var CustomerSegmentationService */
    private $segmentationService;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(
        ResourceConnection $resource,
        SyncStateServiceInterface $syncStateService,
        CustomerSegmentationService $segmentationService,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->syncStateService = $syncStateService;
        $this->segmentationService = $segmentationService;
        $this->logger = $logger;
    }

    public function execute(): int
    {
        $state = $this->syncStateService->getState(self::SYNC_CODE);
        $lastSyncedAt = $state['last_synced_at'] ?? null;

        $this->syncStateService->setStatus(self::SYNC_CODE, 'running', 'Customer segmentation sync in progress');

        try {
            $conn = $this->resource->getConnection();
            $customerAnalyticsTable = $this->resource->getTableName('artm_adminai_customer_analytics');

            $select = $conn->select()
                ->from(['ca' => $customerAnalyticsTable], ['customer_id' => 'ca.customer_id', 'updated_at' => 'ca.updated_at'])
                ->order('ca.updated_at ASC');

            if ($lastSyncedAt !== null && $lastSyncedAt !== '') {
                $select->where('ca.updated_at > ?', $lastSyncedAt);
            }

            $customerRows = $conn->fetchAll($select);

            if (empty($customerRows)) {
                $this->syncStateService->saveState(
                    self::SYNC_CODE,
                    gmdate('Y-m-d H:i:s'),
                    null,
                    'idle',
                    'No customer rows updated for segmentation'
                );
                return 0;
            }

            $customerIds = [];
            $watermark = null;
            foreach ($customerRows as $row) {
                $customerIds[] = (int)$row['customer_id'];
                $watermark = (string)$row['updated_at'];
            }

            $inserted = $this->segmentationService->processCustomers($customerIds);

            $this->syncStateService->saveState(
                self::SYNC_CODE,
                $watermark ?: gmdate('Y-m-d H:i:s'),
                null,
                'idle',
                'Customer segmentation sync complete: ' . $inserted . ' rows'
            );

            return $inserted;
        } catch (\Throwable $e) {
            $this->syncStateService->setStatus(self::SYNC_CODE, 'failed', 'Customer segmentation sync failed');
            $this->logger->error('CustomerSegmentationSync failed', ['exception' => $e]);
            throw $e;
        }
    }
}
