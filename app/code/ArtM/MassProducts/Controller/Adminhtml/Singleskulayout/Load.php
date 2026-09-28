<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Singleskulayout;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Load extends Action
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
            $connection = $this->resource->getConnection();
            $table = $connection->getTableName('artm_single_sku_layout');
            $groupLayoutTable = $connection->getTableName('artm_single_sku_group_layout');

            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($table, ['attribute_set_id', 'attribute_code', 'group_name', 'is_enabled', 'sort_order'])
                    ->where('attribute_set_id IN (?)', [0, $setId])
                    ->where('is_enabled = ?', 1)
                    ->order('attribute_set_id ASC')
                    ->order('sort_order ASC')
            );

            $resolvedByCode = [];
            foreach ($rows as $row) {
                $code = strtolower(trim((string)($row['attribute_code'] ?? '')));
                if ($code === '') {
                    continue;
                }
                $rowSetId = (int)($row['attribute_set_id'] ?? 0);
                if (!isset($resolvedByCode[$code]) || $rowSetId === $setId) {
                    $resolvedByCode[$code] = [
                        'attribute_code' => $code,
                        'group_name' => (string)($row['group_name'] ?? 'Basic Info'),
                        'sort_order' => (int)($row['sort_order'] ?? 0),
                        'attribute_set_id' => $rowSetId,
                    ];
                }
            }

            $placementRows = $connection->fetchAll(
                $connection->select()
                    ->from($groupLayoutTable, ['group_name', 'column_index', 'sort_order'])
                    ->where('attribute_set_id = ?', $setId)
            );

            $placementMap = [];
            foreach ($placementRows as $placementRow) {
                $groupName = trim((string)($placementRow['group_name'] ?? ''));
                if ($groupName === '') {
                    continue;
                }
                $rawColumn = $placementRow['column_index'] ?? null;
                $rawSort = $placementRow['sort_order'] ?? null;
                $columnIndex = (int)$rawColumn;
                $sortOrder = (int)$rawSort;
                if ($columnIndex < 1 || $columnIndex > 3) {
                    $columnIndex = 1;
                }
                if ($sortOrder <= 0) {
                    $sortOrder = 1000;
                }
                $placementMap[strtolower($groupName)] = [
                    'column_index' => $columnIndex,
                    'sort_order' => $sortOrder,
                ];
            }

            $grouped = [];
            foreach ($resolvedByCode as $item) {
                $group = trim((string)$item['group_name']);
                if ($group === '') {
                    $group = 'Basic Info';
                }
                if (!isset($grouped[$group])) {
                    $grouped[$group] = [];
                }
                $grouped[$group][] = [
                    'attribute_code' => $item['attribute_code'],
                    'sort_order' => $item['sort_order'],
                ];
            }

            $groups = [];
            foreach ($grouped as $groupName => $attrs) {
                $placementKey = strtolower(trim((string)$groupName));
                $placement = $placementMap[$placementKey] ?? null;
                usort($attrs, static function (array $a, array $b): int {
                    return (int)$a['sort_order'] <=> (int)$b['sort_order'];
                });
                $groups[] = [
                    'group_name' => $groupName,
                    'column_index' => $placement ? (int)$placement['column_index'] : 1,
                    'group_sort_order' => $placement ? (int)$placement['sort_order'] : 1000,
                    'attributes' => array_map(static function (array $row): string {
                        return (string)$row['attribute_code'];
                    }, $attrs),
                ];
            }

            usort($groups, static function (array $a, array $b): int {
                $aSort = (int)($a['group_sort_order'] ?? 1000);
                $bSort = (int)($b['group_sort_order'] ?? 1000);
                if ($aSort <= 0) {
                    $aSort = 1000;
                }
                if ($bSort <= 0) {
                    $bSort = 1000;
                }
                $cmp = $aSort <=> $bSort;
                if ($cmp !== 0) {
                    return $cmp;
                }
                return strcasecmp((string)$a['group_name'], (string)$b['group_name']);
            });

            return $result->setData([
                'success' => true,
                'attribute_set_id' => $setId,
                'groups' => $groups,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
