<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\Logger\ActivityLogger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Redirect;

class MassDelete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';
    private const LOG_TIMEZONE = 'Asia/Kolkata';

    private $resource;
    private $activityLogger;

    public function __construct(Context $context, ResourceConnection $resource, ActivityLogger $activityLogger)
    {
        parent::__construct($context);
        $this->resource = $resource;
        $this->activityLogger = $activityLogger;
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
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');

            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($draftTable, ['entity_id', 'sku'])
                    ->where('entity_id IN (?)', $ids)
            );

            $connection->delete($valueTable, ['draft_id IN (?)' => $ids]);
            $connection->delete($draftTable, ['entity_id IN (?)' => $ids]);

            $user = $this->_auth->getUser();
            $adminUserId = $user ? (int)$user->getId() : 0;
            $adminUsername = $user ? (string)$user->getUserName() : 'system';
            $now = new \DateTime('now', new \DateTimeZone(self::LOG_TIMEZONE));
            foreach ($rows as $row) {
                $sku = trim((string)($row['sku'] ?? ''));
                $this->activityLogger->log([
                    'action_type' => 'deleted',
                    'sku' => $sku,
                    'draft_id' => (int)($row['entity_id'] ?? 0),
                    'admin_user_id' => $adminUserId,
                    'admin_username' => $adminUsername,
                    'details' => $sku . ' deleted by ' . $adminUsername,
                    'comment' => 'Draft row deleted.',
                    'occurred_at' => $now->format('Y-m-d H:i:s'),
                ]);
            }

            $this->messageManager->addSuccessMessage(__('Selected drafts deleted.'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }

        return $redirect->setPath('*/*/index');
    }
}
