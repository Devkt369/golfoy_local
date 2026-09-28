<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;

class MassMoveToQueue extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;

    public function __construct(Context $context, ResourceConnection $resource)
    {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Redirect
    {
        $ids = (array)$this->getRequest()->getParam('selected', []);
        if (empty($ids)) {
            $ids = (array)$this->getRequest()->getParam('ids', []);
        }

        /** @var Redirect $redirect */
        $redirect = $this->resultRedirectFactory->create();

        try {
            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                throw new \RuntimeException('No drafts selected.');
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
                throw new \RuntimeException('Draft contains missing required attributes.');
            }

            // Queue handoff marker only. Product creation happens later in queue processor.
            $connection->update(
                $draftTable,
                ['status' => 'ready'],
                ['entity_id IN (?)' => $ids]
            );

            $this->messageManager->addSuccessMessage(__('Selected drafts moved to queue.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $redirect->setPath('*/*/index');
    }
}
