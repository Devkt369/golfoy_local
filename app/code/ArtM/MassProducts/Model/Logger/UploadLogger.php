<?php
namespace ArtM\MassProducts\Model\Logger;

use ArtM\MassProducts\Model\CronLogFactory;
use ArtM\MassProducts\Model\ProductLogFactory;
use ArtM\MassProducts\Model\ResourceModel\CronLog as CronLogResource;
use ArtM\MassProducts\Model\ResourceModel\ProductLog as ProductLogResource;
use Magento\Framework\App\ResourceConnection;

class UploadLogger
{
    private $cronLogFactory;
    private $cronLogResource;
    private $productLogFactory;
    private $productLogResource;
    private $resource;

    public function __construct(
        CronLogFactory $cronLogFactory,
        CronLogResource $cronLogResource,
        ProductLogFactory $productLogFactory,
        ProductLogResource $productLogResource,
        ResourceConnection $resource
    ) {
        $this->cronLogFactory = $cronLogFactory;
        $this->cronLogResource = $cronLogResource;
        $this->productLogFactory = $productLogFactory;
        $this->productLogResource = $productLogResource;
        $this->resource = $resource;
    }

    public function startCronRun(string $triggeredBy = 'cron', string $message = ''): int
    {
        try {
            $model = $this->cronLogFactory->create();
            $model->setData('started_at', (new \DateTime())->format('Y-m-d H:i:s'));
            $model->setData('triggered_by', $triggeredBy);
            $model->setData('status', 'failed');
            $model->setData('message', $message !== '' ? $message : 'Cron execution started.');
            $this->cronLogResource->save($model);

            return (int)$model->getId();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    public function finishCronRun(int $logId, int $totalProducts, int $successCount, int $failureCount, string $message = ''): void
    {
        if ($logId <= 0) {
            return;
        }

        $status = 'failed';
        if ($totalProducts <= 0 || $failureCount <= 0) {
            $status = 'success';
        } elseif ($successCount > 0 && $failureCount > 0) {
            $status = 'partial';
        }

        try {
            $model = $this->cronLogFactory->create();
            $this->cronLogResource->load($model, $logId);
            if (!$model->getId()) {
                return;
            }

            $model->setData('finished_at', (new \DateTime())->format('Y-m-d H:i:s'));
            $model->setData('total_products', $totalProducts);
            $model->setData('success_count', $successCount);
            $model->setData('failure_count', $failureCount);
            $model->setData('status', $status);
            if ($message !== '') {
                $model->setData('message', $message);
            }
            $this->cronLogResource->save($model);
        } catch (\Throwable $e) {
            // Logging should never break product processing.
        }
    }

    public function logProductUpload(
        int $productId,
        string $sku,
        int $draftId,
        string $uploadedBy,
        string $status,
        string $message = ''
    ): void {
        try {
            $model = $this->productLogFactory->create();
            $model->setData('product_id', $productId > 0 ? $productId : null);
            $model->setData('sku', $sku);
            $model->setData('draft_id', $draftId > 0 ? $draftId : null);
            $model->setData('uploaded_at', (new \DateTime())->format('Y-m-d H:i:s'));
            $model->setData('uploaded_by', $uploadedBy);
            $model->setData('status', $status);
            $model->setData('message', $message);
            $this->productLogResource->save($model);
        } catch (\Throwable $e) {
            // Logging should never break product processing.
        }
    }

    public function updateDraftLastUpload(int $draftId, string $uploadedBy): void
    {
        if ($draftId <= 0) {
            return;
        }

        try {
            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');
            if (!$connection->isTableExists($draftTable)) {
                return;
            }

            $columns = $connection->describeTable($draftTable);
            $update = [];
            if (isset($columns['last_uploaded_at'])) {
                $update['last_uploaded_at'] = (new \DateTime())->format('Y-m-d H:i:s');
            }
            if (isset($columns['last_uploaded_by'])) {
                $update['last_uploaded_by'] = $uploadedBy;
            }

            if (!empty($update)) {
                $connection->update($draftTable, $update, ['entity_id = ?' => $draftId]);
            }
        } catch (\Throwable $e) {
            // Logging should never break product processing.
        }
    }
}
