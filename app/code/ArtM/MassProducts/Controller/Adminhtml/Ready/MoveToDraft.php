<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Ready;

use ArtM\MassProducts\Model\Logger\ActivityLogger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;

class MoveToDraft extends Action
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
            if (empty($ids)) {
                $single = (int)$this->getRequest()->getParam('id', 0);
                if ($single > 0) {
                    $ids = [$single];
                }
            }

            $ids = array_values(array_filter(array_map('intval', $ids)));
            if (empty($ids)) {
                throw new LocalizedException(__('No ready rows selected.'));
            }

            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');

            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($draftTable, ['entity_id', 'sku'])
                    ->where('entity_id IN (?)', $ids)
            );

            $updated = $connection->update(
                $draftTable,
                [
                    'status' => 'draft',
                    'drafted_by' => (($this->_auth->getUser() ? (string)$this->_auth->getUser()->getUserName() : 'system')),
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s')
                ],
                ['entity_id IN (?)' => $ids]
            );

            $user = $this->_auth->getUser();
            $adminUserId = $user ? (int)$user->getId() : 0;
            $adminUsername = $user ? (string)$user->getUserName() : 'system';
            $now = new \DateTime('now', new \DateTimeZone(self::LOG_TIMEZONE));
            foreach ($rows as $row) {
                $sku = trim((string)($row['sku'] ?? ''));
                $detail = $sku . ' sent to Draft from ready by ' . $adminUsername;

                $this->activityLogger->log([
                    'action_type' => 'sent_to_draft',
                    'sku' => $sku,
                    'draft_id' => (int)($row['entity_id'] ?? 0),
                    'admin_user_id' => $adminUserId,
                    'admin_username' => $adminUsername,
                    'details' => $detail,
                    'comment' => 'Moved from ready to draft.',
                    'occurred_at' => $now->format('Y-m-d H:i:s'),
                ]);
            }

            return $result->setData([
                'success' => true,
                'updated' => (int)$updated,
                'message' => (string)__('Selected rows moved to Draft.'),
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
