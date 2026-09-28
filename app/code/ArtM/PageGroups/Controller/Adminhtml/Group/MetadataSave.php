<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;

class MetadataSave extends Action
{
    const ADMIN_RESOURCE = 'ArtM_PageGroups::main';

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        ResourceConnection $resourceConnection
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Save group-level attributes and per-image attribute values.
     *
     * POST:
     *  group_id int
     *  attributes_json JSON [{attribute_id?, attribute_code?, label?, sort_order?}, ...]
     *  values_json JSON {"<image_id>": {"<attribute_code>": "value"}}
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        if (!$this->getRequest()->isPost()) {
            return $resultJson->setData(['success' => false, 'message' => __('Invalid request method.')]);
        }

        $groupId = (int)$this->getRequest()->getParam('group_id');
        $attributes = json_decode((string)$this->getRequest()->getParam('attributes_json', '[]'), true);
        $values = json_decode((string)$this->getRequest()->getParam('values_json', '{}'), true);

        if ($groupId <= 0) {
            return $resultJson->setData(['success' => false, 'message' => __('group_id is required.')]);
        }
        if (!is_array($attributes)) {
            $attributes = [];
        }
        if (!is_array($values)) {
            $values = [];
        }

        try {
            $connection = $this->resourceConnection->getConnection();
            $groupTable = $this->resourceConnection->getTableName('artm_pg_groups');
            $attributeTable = $this->resourceConnection->getTableName('artm_pg_attributes');
            $groupAttributeTable = $this->resourceConnection->getTableName('artm_pg_group_attributes');
            $imageTable = $this->resourceConnection->getTableName('artm_pg_group_images');
            $valueTable = $this->resourceConnection->getTableName('artm_pg_image_attribute_values');

            foreach ([$groupTable, $attributeTable, $groupAttributeTable, $imageTable, $valueTable] as $tableName) {
                if (!$connection->isTableExists($tableName)) {
                    return $resultJson->setData([
                        'success' => false,
                        'message' => __('Table %1 does not exist. Run setup:upgrade first.', $tableName),
                    ]);
                }
            }

            $groupExists = (int)$connection->fetchOne(
                $connection->select()
                    ->from($groupTable, new \Zend_Db_Expr('COUNT(1)'))
                    ->where('group_id = ?', $groupId)
            );
            if (!$groupExists) {
                return $resultJson->setData(['success' => false, 'message' => __('Group does not exist.')]);
            }

            $connection->beginTransaction();

            $attachedAttributeIds = [];
            $codeToGroupAttributeId = [];

            foreach ($attributes as $index => $attribute) {
                if (!is_array($attribute)) {
                    continue;
                }

                $attributeId = isset($attribute['attribute_id']) ? (int)$attribute['attribute_id'] : 0;
                $code = $this->normalizeCode((string)($attribute['attribute_code'] ?? ''));
                $label = trim((string)($attribute['label'] ?? ''));

                if ($attributeId <= 0) {
                    if ($code === '') {
                        $code = $this->normalizeCode($label);
                    }
                    if ($code === '') {
                        continue;
                    }
                    if ($label === '') {
                        $label = ucwords(str_replace('_', ' ', $code));
                    }

                    $existingId = (int)$connection->fetchOne(
                        $connection->select()
                            ->from($attributeTable, 'attribute_id')
                            ->where('attribute_code = ?', $code)
                            ->limit(1)
                    );

                    if ($existingId > 0) {
                        $attributeId = $existingId;
                    } else {
                        $connection->insert($attributeTable, [
                            'attribute_code' => $code,
                            'label' => $label,
                        ]);
                        $attributeId = (int)$connection->lastInsertId($attributeTable);
                    }
                }

                if ($attributeId <= 0) {
                    continue;
                }

                $attributeRow = $connection->fetchRow(
                    $connection->select()
                        ->from($attributeTable, ['attribute_id', 'attribute_code', 'label'])
                        ->where('attribute_id = ?', $attributeId)
                        ->limit(1)
                );
                if (!$attributeRow) {
                    continue;
                }

                $attachedAttributeIds[] = (int)$attributeRow['attribute_id'];
                $sortOrder = ($index + 1) * 10;

                $groupAttributeId = (int)$connection->fetchOne(
                    $connection->select()
                        ->from($groupAttributeTable, 'group_attribute_id')
                        ->where('group_id = ?', $groupId)
                        ->where('attribute_id = ?', (int)$attributeRow['attribute_id'])
                        ->limit(1)
                );

                if ($groupAttributeId > 0) {
                    $connection->update(
                        $groupAttributeTable,
                        ['sort_order' => $sortOrder],
                        ['group_attribute_id = ?' => $groupAttributeId]
                    );
                } else {
                    $connection->insert($groupAttributeTable, [
                        'group_id' => $groupId,
                        'attribute_id' => (int)$attributeRow['attribute_id'],
                        'sort_order' => $sortOrder,
                    ]);
                    $groupAttributeId = (int)$connection->lastInsertId($groupAttributeTable);
                }

                $codeToGroupAttributeId[(string)$attributeRow['attribute_code']] = $groupAttributeId;
            }

            if (!empty($attachedAttributeIds)) {
                $connection->delete(
                    $groupAttributeTable,
                    [
                        'group_id = ?' => $groupId,
                        'attribute_id NOT IN (?)' => $attachedAttributeIds,
                    ]
                );
            } else {
                $connection->delete($groupAttributeTable, ['group_id = ?' => $groupId]);
            }

            $imageIds = $connection->fetchCol(
                $connection->select()
                    ->from($imageTable, ['image_id'])
                    ->where('group_id = ?', $groupId)
            );
            $imageIds = array_map('intval', $imageIds);

            if (!empty($imageIds)) {
                $connection->delete($valueTable, ['image_id IN (?)' => $imageIds]);

                foreach ($values as $imageIdKey => $imageValues) {
                    $imageId = (int)$imageIdKey;
                    if ($imageId <= 0 || !in_array($imageId, $imageIds, true) || !is_array($imageValues)) {
                        continue;
                    }

                    foreach ($imageValues as $attributeCode => $valueText) {
                        $attributeCode = $this->normalizeCode((string)$attributeCode);
                        if ($attributeCode === '' || !isset($codeToGroupAttributeId[$attributeCode])) {
                            continue;
                        }

                        $valueText = trim((string)$valueText);
                        if ($valueText === '') {
                            continue;
                        }

                        $connection->insert($valueTable, [
                            'image_id' => $imageId,
                            'group_attribute_id' => (int)$codeToGroupAttributeId[$attributeCode],
                            'value_text' => $valueText,
                        ]);
                    }
                }
            }

            $connection->commit();

            return $resultJson->setData([
                'success' => true,
                'message' => __('Group metadata saved.'),
            ]);
        } catch (\Throwable $e) {
            try {
                $this->resourceConnection->getConnection()->rollBack();
            } catch (\Throwable $rollbackException) {
                // no-op
            }

            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to save metadata: %1', $e->getMessage()),
            ]);
        }
    }

    /**
     * @param string $value
     * @return string
     */
    private function normalizeCode($value)
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        return trim((string)$value, '_');
    }
}
