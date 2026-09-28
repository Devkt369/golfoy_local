<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Stats;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class TabCounts extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    /** @var ResourceConnection */
    private $resource;

    public function __construct(Context $context, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $connection = $this->resource->getConnection();
            $counts = [
                'draft' => 0,
                'ready' => 0,
                'queued' => 0,
                'processing' => 0,
                'failed' => 0,
                'success' => 0,
            ];

            $draftTable = $connection->getTableName('artm_massproducts_draft');
            if ($connection->isTableExists($draftTable)) {
                $rows = $connection->fetchAll(
                    $connection->select()
                        ->from($draftTable, ['status', 'cnt' => 'COUNT(*)'])
                        ->group('status')
                );

                foreach ($rows as $row) {
                    $status = strtolower(trim((string)($row['status'] ?? '')));
                    $cnt = (int)($row['cnt'] ?? 0);

                    if ($status === 'incomplete' || $status === 'draft') {
                        $counts['draft'] += $cnt;
                        continue;
                    }

                    if (array_key_exists($status, $counts)) {
                        $counts[$status] += $cnt;
                    }
                }
            }

            // Backward compatibility: if queue pipeline writes statuses into intake table.
            $intakeTable = $connection->getTableName('artm_intake_products');
            if ($connection->isTableExists($intakeTable)) {
                $rows = $connection->fetchAll(
                    $connection->select()
                        ->from($intakeTable, ['status', 'cnt' => 'COUNT(*)'])
                        ->group('status')
                );

                foreach ($rows as $row) {
                    $status = strtolower(trim((string)($row['status'] ?? '')));
                    $cnt = (int)($row['cnt'] ?? 0);

                    if ($status === 'queued' || $status === 'processing' || $status === 'failed' || $status === 'success') {
                        $counts[$status] += $cnt;
                    }
                }
            }

            return $result->setData([
                'success' => true,
                'counts' => $counts,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
