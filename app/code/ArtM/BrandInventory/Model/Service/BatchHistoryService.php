<?php

namespace ArtM\BrandInventory\Model\Service;

use Magento\Framework\App\ResourceConnection;

class BatchHistoryService
{
    protected $resource;

    public function __construct(ResourceConnection $resource)
    {
        $this->resource = $resource;
    }

    public function appendComment($batchId, $action, $user, $details = null)
    {
        try {
            $connection = $this->resource->getConnection();
            $batchTable = $this->resource->getTableName('artm_brand_inventory');

            $existing = $connection->fetchOne(
                "SELECT comments FROM {$batchTable} WHERE id = ?",
                [$batchId]
            );

            $comments = $this->decodeComments($existing);
            $comment = [
                'action' => $action,
                'label' => $this->getActionLabel($action),
                'user' => $user ?: 'system',
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            if ($details && is_array($details)) {
                $comment['details'] = $details;
            }
            
            $comments[] = $comment;

            $connection->update(
                $batchTable,
                ['comments' => json_encode($comments, JSON_UNESCAPED_UNICODE)],
                ['id = ?' => $batchId]
            );

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getComments($batchId)
    {
        try {
            $connection = $this->resource->getConnection();
            $batchTable = $this->resource->getTableName('artm_brand_inventory');

            $summaryLogs = $connection->fetchOne(
                "SELECT comments FROM {$batchTable} WHERE id = ?",
                [$batchId]
            );

            // Decode JSON string into array
            $comments = $this->decodeComments($summaryLogs);

            // Convert created_at to Indian timezone (Asia/Kolkata)
            $indianTimezone = new \DateTimeZone('Asia/Kolkata');
            foreach ($comments as &$comment) {
                if (!empty($comment['created_at'])) {
                    $utcDate = new \DateTime($comment['created_at'], new \DateTimeZone('UTC'));
                    $utcDate->setTimezone($indianTimezone);
                    $comment['created_at'] = $utcDate->format('d-M-Y h:i A');
                }
            }
            unset($comment);

            return $comments;
        } catch (\Exception $e) {
            return [];
        }
    }

    private function decodeComments($raw)
    {
        if (empty($raw)) {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
}

    private function getActionLabel($action)
    {
        switch ($action) {
            case 'uploaded':
                return 'Uploaded by';
            case 'loaded':
                return 'Loaded by';
            case 'sku_updated':
                return 'Sku updated by';
            case 'sku_updated_bulk':
                return 'Bulk sku updated by';
            case 'synced':
                return 'Synced sku by';
            case 'synced_inventory':
                return 'Synced inv by';
            default:
                return $action;
        }
    }
}
