<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Singleskulayout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class GroupSave extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    /** @var ResourceConnection */
    private $resource;

    public function __construct(
        Context $context,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resource = $resource;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $setId = (int)$this->getRequest()->getParam('attribute_set_id', 0);
            $groupsRaw = (string)$this->getRequest()->getParam('groups', '[]');
            $groups = json_decode($groupsRaw, true);
            if (!is_array($groups)) {
                throw new \InvalidArgumentException('Invalid groups payload.');
            }

            $connection = $this->resource->getConnection();
            $layoutTable = $connection->getTableName('artm_single_sku_layout');
            $groupLayoutTable = $connection->getTableName('artm_single_sku_group_layout');

            $connection->beginTransaction();
            try {
                $insertRows = [];

                foreach ($groups as $group) {
                    if (!is_array($group)) {
                        continue;
                    }
                    $originalName = trim((string)($group['original_name'] ?? ''));
                    $groupName = trim((string)($group['group_name'] ?? ''));
                    if ($groupName === '') {
                        continue;
                    }
                    $isDeleted = !empty($group['is_deleted']);

                    if ($isDeleted) {
                        if ($originalName !== '') {
                            $connection->update(
                                $layoutTable,
                                ['group_name' => 'Custom Specifications'],
                                [
                                    'attribute_set_id = ?' => $setId,
                                    'group_name = ?' => $originalName,
                                ]
                            );
                            $connection->delete(
                                $groupLayoutTable,
                                [
                                    'attribute_set_id = ?' => $setId,
                                    'group_name = ?' => $originalName,
                                ]
                            );
                        }
                        continue;
                    }

                    if ($originalName !== '' && strcasecmp($originalName, $groupName) !== 0) {
                        $connection->update(
                            $layoutTable,
                            ['group_name' => $groupName],
                            [
                                'attribute_set_id = ?' => $setId,
                                'group_name = ?' => $originalName,
                            ]
                        );
                        $connection->update(
                            $groupLayoutTable,
                            ['group_name' => $groupName],
                            [
                                'attribute_set_id = ?' => $setId,
                                'group_name = ?' => $originalName,
                            ]
                        );
                    }

                    $insertRows[] = [
                        'attribute_set_id' => $setId,
                        'group_name' => $groupName,
                        'is_enabled' => !empty($group['is_enabled']) ? 1 : 0,
                        'column_index' => max(1, min(3, (int)($group['column_index'] ?? 1))),
                        'sort_order' => max(0, (int)($group['sort_order'] ?? 0)),
                    ];
                }

                $connection->delete($groupLayoutTable, ['attribute_set_id = ?' => $setId]);
                if ($insertRows) {
                    $connection->insertMultiple($groupLayoutTable, $insertRows);
                }

                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                throw $e;
            }

            return $result->setData([
                'success' => true,
                'message' => 'Group layout saved.',
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
