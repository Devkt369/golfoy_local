<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class TabMoveToQueue extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

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
            $ids = (array)$this->getRequest()->getParam('ids', []);
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                throw new LocalizedException(__('No drafts selected.'));
            }

            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');

            $rows = $connection->fetchAll(
                $connection->select()->from($draftTable, ['entity_id', 'status'])->where('entity_id IN (?)', $ids)
            );
            $notReady = [];
            foreach ($rows as $row) {
                if ((string)($row['status'] ?? '') !== 'ready') {
                    $notReady[] = (int)$row['entity_id'];
                }
            }

            if (!empty($notReady)) {
                throw new LocalizedException(__('Draft contains missing required attributes.'));
            }

            // Update status to 'queued' for selected drafts
            $updated = $connection->update($draftTable, [
                'status' => 'queued',
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ], ['entity_id IN (?)' => $ids]);

            return $result->setData([
                'success' => true,
                'updated' => (int)$updated,
                'message' => 'Selected drafts moved to queue.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
