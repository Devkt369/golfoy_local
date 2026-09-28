<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Singleskulayout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Groups extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';
    private const MAIN_GROUP = 'Main';
    private const DEFAULT_MAIN_SORT_ORDER = 1;
    private const DEFAULT_MAIN_WIDTH = 2;

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
            $connection = $this->resource->getConnection();

            $layoutTable      = $connection->getTableName('artm_single_sku_layout');
            $groupLayoutTable = $connection->getTableName('artm_single_sku_group_layout');
            $attrTable        = $connection->getTableName('eav_attribute');
            $entityTypeTable  = $connection->getTableName('eav_entity_type');

            $entityTypeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($entityTypeTable, ['entity_type_id'])
                    ->where('entity_type_code = ?', 'catalog_product')
                    ->limit(1)
            );

                // All attribute rows for this set with global fallback (set 0).
            $attrRows = $connection->fetchAll(
                $connection->select()
                    ->from($layoutTable, ['attribute_set_id', 'group_name', 'attribute_code', 'is_enabled', 'sort_order'])
                    ->where('attribute_set_id IN (?)', [0, $setId])
                    ->order('group_name ASC')
                    ->order('sort_order ASC')
                    ->order('attribute_code ASC')
            );

                // Group-level placement metadata with global fallback (set 0).
            $placementRows = $connection->fetchAll(
                $connection->select()
                    ->from($groupLayoutTable, ['attribute_set_id', 'group_name', 'is_enabled', 'column_index', 'sort_order'])
                    ->where('attribute_set_id IN (?)', [0, $setId])
            );

            $placementMap = [];
            foreach ($placementRows as $row) {
                $name = trim((string)($row['group_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $rowSetId = (int)($row['attribute_set_id'] ?? 0);
                $sortOrder = (int)($row['sort_order'] ?? 0);
                if ($sortOrder <= 0) {
                    $sortOrder = 1000;
                }
                $placementKey = strtolower($name);
                if (isset($placementMap[$placementKey]) && $rowSetId !== $setId) {
                    continue;
                }
                $placementMap[$placementKey] = [
                    'name'         => $name,
                    'is_enabled'   => (int)($row['is_enabled'] ?? 1) === 1,
                    'column_index' => max(1, min(3, (int)($row['column_index'] ?? 1))),
                    'sort_order'   => $sortOrder,
                ];
            }

            $mainPlacementKey = strtolower(self::MAIN_GROUP);
            if (!isset($placementMap[$mainPlacementKey])) {
                $connection->insert($groupLayoutTable, [
                    'attribute_set_id' => $setId,
                    'group_name' => self::MAIN_GROUP,
                    'is_enabled' => 1,
                    'column_index' => self::DEFAULT_MAIN_WIDTH,
                    'sort_order' => self::DEFAULT_MAIN_SORT_ORDER,
                ]);
                $placementMap[$mainPlacementKey] = [
                    'name' => self::MAIN_GROUP,
                    'is_enabled' => true,
                    'column_index' => self::DEFAULT_MAIN_WIDTH,
                    'sort_order' => self::DEFAULT_MAIN_SORT_ORDER,
                ];
            }

            // Collect attribute labels.
            $codes = [];
            foreach ($attrRows as $row) {
                $code = strtolower(trim((string)($row['attribute_code'] ?? '')));
                if ($code !== '') {
                    $codes[] = $code;
                }
            }
            $labelsMap = [];
            if ($codes) {
                $labelRows = $connection->fetchAll(
                    $connection->select()
                        ->from($attrTable, ['attribute_code', 'frontend_label'])
                        ->where('entity_type_id = ?', $entityTypeId)
                        ->where('attribute_code IN (?)', array_values(array_unique($codes)))
                );
                foreach ($labelRows as $lr) {
                    $c = strtolower(trim((string)($lr['attribute_code'] ?? '')));
                    $l = trim((string)($lr['frontend_label'] ?? ''));
                    $labelsMap[$c] = $l !== '' ? $l : $c;
                }
            }

            // Build groups from attribute-level rows (all, not just enabled),
            // preferring set-specific rows over set 0 for the same attribute code.
            $resolvedByCode = [];
            foreach ($attrRows as $row) {
                $code = strtolower(trim((string)($row['attribute_code'] ?? '')));
                if ($code === '') {
                    continue;
                }
                $rowSetId = (int)($row['attribute_set_id'] ?? 0);
                if (!isset($resolvedByCode[$code]) || $rowSetId === $setId) {
                    $resolvedByCode[$code] = $row;
                }
            }

            $groups = [];
            foreach ($resolvedByCode as $row) {
                $groupName = trim((string)($row['group_name'] ?? ''));
                if ($groupName === '') {
                    $groupName = 'Basic Info';
                }
                $groupKey = strtolower($groupName);
                if (!isset($groups[$groupKey])) {
                    $pl = $placementMap[$groupKey] ?? null;
                    $resolvedName = $pl ? (string)$pl['name'] : $groupName;
                    $groups[$groupKey] = [
                        'name'         => $groupName,
                        'is_enabled'   => $pl ? $pl['is_enabled'] : true,
                        'column_index' => $pl ? $pl['column_index'] : 1,
                        'sort_order'   => $pl ? $pl['sort_order'] : 1000,
                        'attributes'   => [],
                    ];
                    $groups[$groupKey]['name'] = $resolvedName;
                }
                $code = strtolower(trim((string)($row['attribute_code'] ?? '')));
                if ($code === '') {
                    continue;
                }
                $groups[$groupKey]['attributes'][] = [
                    'attribute_code' => $code,
                    'label'          => $labelsMap[$code] ?? $code,
                    'is_enabled'     => (int)($row['is_enabled'] ?? 0) === 1,
                ];
            }

            // Merge any groups that only exist in placement table (custom, no attrs yet).
            foreach ($placementMap as $groupKey => $pl) {
                if (!isset($groups[$groupKey])) {
                    $groups[$groupKey] = [
                        'name'         => (string)$pl['name'],
                        'is_enabled'   => $pl['is_enabled'],
                        'column_index' => $pl['column_index'],
                        'sort_order'   => $pl['sort_order'],
                        'attributes'   => [],
                    ];
                }
            }

            $out = array_values($groups);
            usort($out, static function (array $a, array $b): int {
                $cmp = ((int)$a['sort_order'] <=> (int)$b['sort_order']);
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcasecmp((string)$a['name'], (string)$b['name']);
            });

            foreach ($out as &$group) {
                $group['embed_code'] = '{{group:' . $group['name'] . '}}';
                $group['group_name'] = (string)$group['name'];
                $group['group_sort_order'] = (int)$group['sort_order'];
            }
            unset($group);

            return $result->setData([
                'success'          => true,
                'attribute_set_id' => $setId,
                'groups'           => $out,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
