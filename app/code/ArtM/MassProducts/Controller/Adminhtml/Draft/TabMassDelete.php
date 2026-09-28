<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Draft;

use ArtM\MassProducts\Model\Logger\ActivityLogger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class TabMassDelete extends Action
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
            $valueTable = $connection->getTableName('artm_massproducts_draft_values');
            $mediaTable = $connection->getTableName('artm_massproducts_draft_media');
            $hasDraftValuesTable = $connection->isTableExists($valueTable);
            $hasDraftMediaTable = $connection->isTableExists($mediaTable);

            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($draftTable, ['entity_id', 'sku'])
                    ->where('entity_id IN (?)', $ids)
            );

            if ($hasDraftValuesTable) {
                $connection->delete($valueTable, ['draft_id IN (?)' => $ids]);
            }
            if ($hasDraftMediaTable) {
                $connection->delete($mediaTable, ['draft_id IN (?)' => $ids]);
            }
            $deleted = $connection->delete($draftTable, ['entity_id IN (?)' => $ids]);

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

            return $result->setData([
                'success' => true,
                'deleted' => (int)$deleted,
                'message' => 'Selected drafts deleted.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
