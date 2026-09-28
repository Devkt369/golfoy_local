<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;

class Tree extends Action
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
            $connection = $this->resource->getConnection();
            $categoryTable = $connection->getTableName('catalog_category_entity');
            $entityTypeTable = $connection->getTableName('eav_entity_type');
            $attributeTable = $connection->getTableName('eav_attribute');
            $varcharTable = $connection->getTableName('catalog_category_entity_varchar');

            if (!$connection->isTableExists($categoryTable)) {
                return $result->setData(['success' => true, 'items' => []]);
            }

            $entityTypeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($entityTypeTable, ['entity_type_id'])
                    ->where('entity_type_code = ?', 'catalog_category')
                    ->limit(1)
            );

            $nameAttributeId = (int)$connection->fetchOne(
                $connection->select()
                    ->from($attributeTable, ['attribute_id'])
                    ->where('entity_type_id = ?', $entityTypeId)
                    ->where('attribute_code = ?', 'name')
                    ->limit(1)
            );

            $select = $connection->select()
                ->from(['c' => $categoryTable], ['entity_id', 'parent_id', 'level', 'path'])
                ->where('c.level >= ?', 2)
                ->order('c.path ASC');

            if ($nameAttributeId > 0 && $connection->isTableExists($varcharTable)) {
                $select->joinLeft(
                    ['n' => $varcharTable],
                    'n.entity_id = c.entity_id AND n.attribute_id = ' . $nameAttributeId . ' AND n.store_id = 0',
                    ['name' => 'value']
                );
            }

            $rows = $connection->fetchAll($select);

            $nodes = [];
            foreach ($rows as $row) {
                $id = (int)($row['entity_id'] ?? 0);
                if ($id <= 0) {
                    continue;
                }
                $name = trim((string)($row['name'] ?? ''));
                if ($name === '') {
                    $name = 'Category #' . $id;
                }

                $nodes[$id] = [
                    'id' => $id,
                    'name' => $name,
                    'parent_id' => (int)($row['parent_id'] ?? 0),
                    'children' => [],
                ];
            }

            $roots = [];
            foreach ($nodes as $id => &$node) {
                $parentId = (int)$node['parent_id'];
                if ($parentId > 0 && isset($nodes[$parentId])) {
                    $nodes[$parentId]['children'][] = &$node;
                } else {
                    $roots[] = &$node;
                }
            }
            unset($node);

            $roots = array_map(function (array $node): array {
                return $this->stripMeta($node);
            }, $roots);

            return $result->setData([
                'success' => true,
                'items' => $roots,
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function stripMeta(array $node): array
    {
        $children = [];
        foreach ((array)($node['children'] ?? []) as $child) {
            if (!is_array($child)) {
                continue;
            }
            $children[] = $this->stripMeta($child);
        }

        return [
            'id' => (int)($node['id'] ?? 0),
            'name' => (string)($node['name'] ?? ''),
            'children' => $children,
        ];
    }
}
