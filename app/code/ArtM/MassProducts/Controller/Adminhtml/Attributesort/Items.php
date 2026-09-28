<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Attributesort;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Items extends Action
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
            $setId = (int)$this->getRequest()->getParam('attribute_set_id', 0);
            $setId = 0;

            $connection = $this->resource->getConnection();
            $attribute = $connection->getTableName('eav_attribute');
            $entityTypeTable = $connection->getTableName('eav_entity_type');
            $catalogEavAttribute = $connection->getTableName('catalog_eav_attribute');
            $assignmentTable = $connection->getTableName('artm_massproducts_attr_assignment');
            $sortTable = $connection->getTableName('artm_massproducts_attr_sort');

            $hasCatalogEav = $connection->isTableExists($catalogEavAttribute);
            $hasAssign = $connection->isTableExists($assignmentTable);
            $hasSort = $connection->isTableExists($sortTable);

            $entityTypeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($entityTypeTable, ['entity_type_id'])
                    ->where('entity_type_code = ?', 'catalog_product')
                    ->limit(1)
            );

            $select = $connection->select()
                ->from(['a' => $attribute], ['attribute_code', 'frontend_label', 'is_user_defined'])
                ->where('a.entity_type_id = ?', $entityTypeId)
                ->order('a.attribute_code ASC');

            if ($hasCatalogEav) {
                $select->joinLeft(['cea' => $catalogEavAttribute], 'cea.attribute_id = a.attribute_id', []);
                $select->where('COALESCE(cea.is_visible, 1) = ?', 1);
            }

            $rows = $connection->fetchAll($select);

            $globalMap = [];
            if ($hasAssign) {
                foreach ($connection->fetchAll($connection->select()->from($assignmentTable, ['attribute_code', 'is_enabled'])->where('attribute_set_id = ?', 0)) as $row) {
                    $globalMap[(string)$row['attribute_code']] = (int)$row['is_enabled'];
                }
            }

            $customSort = [];
            if ($hasSort) {
                foreach ($connection->fetchAll($connection->select()->from($sortTable, ['attribute_code', 'sort_order'])->where('attribute_set_id = ?', $setId)) as $row) {
                    $customSort[(string)$row['attribute_code']] = (int)$row['sort_order'];
                }
            }

            $items = [];
            $index = 0;
            foreach ($rows as $row) {
                $code = trim((string)($row['attribute_code'] ?? ''));
                if ($code === '') {
                    continue;
                }

                $globalEnabled = array_key_exists($code, $globalMap) ? ((int)$globalMap[$code] === 1) : true;
                if (!$globalEnabled) {
                    continue;
                }

                $label = trim((string)($row['frontend_label'] ?? ''));
                if ($label === '') {
                    $label = $code;
                }

                $items[] = [
                    'attribute_code' => $code,
                    'label' => $label . ' (' . $code . ')',
                    'is_user_defined' => (int)($row['is_user_defined'] ?? 0) === 1,
                    'sort_order' => array_key_exists($code, $customSort) ? (int)$customSort[$code] : $index,
                ];
                $index++;
            }

            usort($items, static function (array $a, array $b): int {
                $aGroup = !empty($a['is_user_defined']) ? 1 : 0;
                $bGroup = !empty($b['is_user_defined']) ? 1 : 0;
                if ($aGroup !== $bGroup) {
                    return $aGroup <=> $bGroup;
                }

                $aOrder = (int)($a['sort_order'] ?? 0);
                $bOrder = (int)($b['sort_order'] ?? 0);
                if ($aOrder !== $bOrder) {
                    return $aOrder <=> $bOrder;
                }

                return ((string)($a['attribute_code'] ?? '')) <=> ((string)($b['attribute_code'] ?? ''));
            });

            return $result->setData([
                'success' => true,
                'items' => $items,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
