<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Media;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use ArtM\MassProducts\Model\MediaTableManager;

class UpdateRoles extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private ResourceConnection $resource;
    private MediaTableManager $mediaTableManager;

    public function __construct(Context $context, ResourceConnection $resource, MediaTableManager $mediaTableManager)
    {
        parent::__construct($context);
        $this->resource = $resource;
        $this->mediaTableManager = $mediaTableManager;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $mediaId = (int)$this->getRequest()->getParam('media_id', 0);
            if ($mediaId <= 0) {
                return $result->setData(['success' => false, 'message' => 'Invalid media ID.']);
            }

            $rawRoles = $this->getRequest()->getParam('roles', []);
            if (!is_array($rawRoles)) {
                $rawRoles = [];
            }

            $allowedRoles = ['base', 'small', 'thumbnail'];
            $roles = array_values(array_intersect($rawRoles, $allowedRoles));

            $connection = $this->resource->getConnection();
            if (!$this->mediaTableManager->exists()) {
                return $result->setData(['success' => false, 'message' => 'Media storage table is not initialized yet.']);
            }
            $table = $this->mediaTableManager->getTableName();

            $row = $connection->fetchRow(
                $connection->select()->from($table)->where('media_id = ?', $mediaId)
            );
            if (!$row) {
                return $result->setData(['success' => false, 'message' => 'Media record not found.']);
            }

            $draftId = (int)$row['draft_id'];

            // Each role is exclusive per draft – unset from siblings first
            if (!empty($roles)) {
                $siblings = $connection->fetchAll(
                    $connection->select()
                        ->from($table, ['media_id', 'roles'])
                        ->where('draft_id = ?', $draftId)
                        ->where('media_id != ?', $mediaId)
                );

                foreach ($siblings as $sibling) {
                    $sibRoles = array_filter(explode(',', (string)($sibling['roles'] ?? '')));
                    $newSibRoles = array_values(array_diff($sibRoles, $roles));
                    if (count($newSibRoles) !== count($sibRoles)) {
                        $connection->update(
                            $table,
                            ['roles' => implode(',', $newSibRoles)],
                            ['media_id = ?' => (int)$sibling['media_id']]
                        );
                    }
                }
            }

            $connection->update($table, ['roles' => implode(',', $roles)], ['media_id = ?' => $mediaId]);

            return $result->setData(['success' => true, 'roles' => $roles]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
