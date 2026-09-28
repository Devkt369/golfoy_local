<?php
namespace ArtM\CategoryPage\Model;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;

class ConfigRepository
{
    private const CACHE_TAG = 'ARTM_CATEGORYPAGE';

    private $resource;
    private $cache;
    private $hasManualGroupCategoryColumn;

    public function __construct(ResourceConnection $resource, CacheInterface $cache)
    {
        $this->resource = $resource;
        $this->cache = $cache;
    }

    public function getCategoryConfigMap(array $categoryIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $categoryIds))));
        if (empty($ids)) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_categorypage_config');
        $rows = $connection->fetchAll(
            $connection->select()->from($table)->where('category_id IN (?)', $ids)
        );

        $result = [];
        foreach ($rows as $row) {
            $result[(int)$row['category_id']] = $this->normalizeConfigRow($row);
        }

        return $result;
    }

    public function getCategoryConfig(int $categoryId): array
    {
        $map = $this->getCategoryConfigMap([$categoryId]);
        return $map[$categoryId] ?? [];
    }

    public function saveCategoryConfig(array $data): void
    {
        $categoryId = (int)($data['category_id'] ?? 0);
        if ($categoryId <= 0) {
            throw new \InvalidArgumentException('Invalid category id.');
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_categorypage_config');
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $existing = $this->getCategoryConfig($categoryId);
        $row = [
            'category_id' => $categoryId,
            'custom_label' => array_key_exists('custom_label', $data) ? trim((string)$data['custom_label']) : (string)($existing['custom_label'] ?? ''),
            'category_image' => array_key_exists('category_image', $data) ? trim((string)$data['category_image']) : (string)($existing['category_image'] ?? ''),
            'banner_image' => array_key_exists('banner_image', $data) ? trim((string)$data['banner_image']) : (string)($existing['banner_image'] ?? ''),
            'banner_link' => array_key_exists('banner_link', $data) ? trim((string)$data['banner_link']) : (string)($existing['banner_link'] ?? ''),
            'is_enabled' => array_key_exists('is_enabled', $data) ? (!empty($data['is_enabled']) ? 1 : 0) : (int)($existing['is_enabled'] ?? 1),
            'sort_order' => array_key_exists('sort_order', $data) ? (int)$data['sort_order'] : (int)($existing['sort_order'] ?? 0),
            'updated_at' => $now,
        ];

        if (empty($existing)) {
            $row['created_at'] = $now;
        }

        $connection->insertOnDuplicate($table, $row, ['custom_label', 'category_image', 'banner_image', 'banner_link', 'is_enabled', 'sort_order', 'updated_at']);
        $this->cache->clean([self::CACHE_TAG, 'catalog_category_' . $categoryId]);
    }

    public function getManualCategories(int $parentCategoryId): array
    {
        if ($parentCategoryId <= 0) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_categorypage_manual');
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($table)
                ->where('parent_category_id = ?', $parentCategoryId)
                ->order('sort_order ASC')
                ->order('entity_id ASC')
        );

        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->normalizeManualRow($row);
        }

        return $result;
    }

    public function saveManualCategory(array $data): int
    {
        $parentCategoryId = (int)($data['parent_category_id'] ?? 0);
        if ($parentCategoryId <= 0) {
            throw new \InvalidArgumentException('Invalid parent category id.');
        }

        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') {
            throw new \InvalidArgumentException('Title is required.');
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_categorypage_manual');
        $now = (new \DateTime())->format('Y-m-d H:i:s');
        $entityId = (int)($data['entity_id'] ?? 0);
        $requestedGroupCategoryId = ($data['group_category_id'] ?? '') !== '' ? (int)$data['group_category_id'] : 0;

        if ($requestedGroupCategoryId !== 0 && !$this->hasManualGroupCategoryColumn()) {
            throw new \RuntimeException('Manual cards can be added to a specific group only after running bin/magento setup:upgrade.');
        }

        $row = [
            'parent_category_id' => $parentCategoryId,
            'title' => $title,
            'image' => trim((string)($data['image'] ?? '')),
            'url' => trim((string)($data['url'] ?? '')),
            'sort_order' => (int)($data['sort_order'] ?? 0),
            'is_enabled' => !empty($data['is_enabled']) ? 1 : 0,
            'updated_at' => $now,
        ];

        if ($this->hasManualGroupCategoryColumn()) {
            $row['group_category_id'] = $requestedGroupCategoryId !== 0 ? $requestedGroupCategoryId : null;
        }

        if ($entityId > 0) {
            $connection->update($table, $row, ['entity_id = ?' => $entityId]);
            $this->cache->clean([self::CACHE_TAG, 'catalog_category_' . $parentCategoryId]);
            return $entityId;
        }

        $row['created_at'] = $now;
        $connection->insert($table, $row);
        $newId = (int)$connection->lastInsertId($table);
        $this->cache->clean([self::CACHE_TAG, 'catalog_category_' . $parentCategoryId]);
        return $newId;
    }

    public function saveSortOrders(string $scope, array $items): void
    {
        $scope = strtolower(trim($scope));
        if (!in_array($scope, ['config', 'manual'], true)) {
            throw new \InvalidArgumentException('Invalid sort scope.');
        }

        $connection = $this->resource->getConnection();
        $now = (new \DateTime())->format('Y-m-d H:i:s');

        if ($scope === 'config') {
            $table = $connection->getTableName('artm_categorypage_config');
            foreach ($items as $item) {
                $categoryId = (int)($item['category_id'] ?? $item['entity_id'] ?? 0);
                if ($categoryId <= 0) {
                    continue;
                }
                $existing = $this->getCategoryConfig($categoryId);
                $row = [
                    'category_id' => $categoryId,
                    'sort_order' => (int)($item['sort_order'] ?? 0),
                    'updated_at' => $now,
                    'is_enabled' => isset($existing['is_enabled']) ? (int)$existing['is_enabled'] : 1,
                    'custom_label' => (string)($existing['custom_label'] ?? ''),
                    'category_image' => (string)($existing['category_image'] ?? ''),
                    'banner_image' => (string)($existing['banner_image'] ?? ''),
                    'banner_link' => (string)($existing['banner_link'] ?? ''),
                ];
                if (empty($existing)) {
                    $row['created_at'] = $now;
                }
                $connection->insertOnDuplicate($table, $row, ['sort_order', 'updated_at']);
            }
        } else {
            $table = $connection->getTableName('artm_categorypage_manual');
            foreach ($items as $item) {
                $entityId = (int)($item['entity_id'] ?? 0);
                if ($entityId <= 0) {
                    continue;
                }
                $connection->update($table, ['sort_order' => (int)($item['sort_order'] ?? 0), 'updated_at' => $now], ['entity_id = ?' => $entityId]);
            }
        }

        $this->cache->clean([self::CACHE_TAG]);
    }

    public function resetCategoryData(int $categoryId): void
    {
        if ($categoryId <= 0) {
            throw new \InvalidArgumentException('Invalid category id.');
        }

        $connection = $this->resource->getConnection();
        $categoryTable = $connection->getTableName('catalog_category_entity');
        $configTable = $connection->getTableName('artm_categorypage_config');
        $manualTable = $connection->getTableName('artm_categorypage_manual');
        $manualGroupTable = $connection->getTableName('artm_categorypage_manual_groups');

        $path = (string)$connection->fetchOne(
            $connection->select()
                ->from($categoryTable, ['path'])
                ->where('entity_id = ?', $categoryId)
                ->limit(1)
        );

        if ($path === '') {
            throw new \InvalidArgumentException('Category not found.');
        }

        $descendantIds = $connection->fetchCol(
            $connection->select()
                ->from($categoryTable, ['entity_id'])
                ->where('path = ?', $path)
                ->orWhere('path LIKE ?', $path . '/%')
        );

        $descendantIds = array_values(array_unique(array_map('intval', $descendantIds)));
        if (empty($descendantIds)) {
            $descendantIds = [$categoryId];
        }

        $connection->delete($configTable, ['category_id IN (?)' => $descendantIds]);
        $connection->delete($manualTable, ['parent_category_id = ?' => $categoryId]);
        $connection->delete($manualGroupTable, ['category_id = ?' => $categoryId]);

        $tags = [self::CACHE_TAG, 'catalog_category_' . $categoryId];
        foreach ($descendantIds as $id) {
            $tags[] = 'catalog_category_' . (int)$id;
        }
        $this->cache->clean(array_values(array_unique($tags)));
    }

    private function normalizeConfigRow(array $row): array
    {
        return [
            'entity_id' => (int)($row['entity_id'] ?? 0),
            'category_id' => (int)($row['category_id'] ?? 0),
            'custom_label' => (string)($row['custom_label'] ?? ''),
            'category_image' => (string)($row['category_image'] ?? ''),
            'banner_image' => (string)($row['banner_image'] ?? ''),
            'banner_link' => (string)($row['banner_link'] ?? ''),
            'is_enabled' => (int)($row['is_enabled'] ?? 0),
            'sort_order' => (int)($row['sort_order'] ?? 0),
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }

    private function normalizeManualRow(array $row): array
    {
        return [
            'entity_id' => (int)($row['entity_id'] ?? 0),
            'parent_category_id' => (int)($row['parent_category_id'] ?? 0),
            'group_category_id' => isset($row['group_category_id']) && $row['group_category_id'] !== null ? (int)$row['group_category_id'] : 0,
            'title' => (string)($row['title'] ?? ''),
            'image' => (string)($row['image'] ?? ''),
            'url' => (string)($row['url'] ?? ''),
            'sort_order' => (int)($row['sort_order'] ?? 0),
            'is_enabled' => (int)($row['is_enabled'] ?? 0),
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }

    private function hasManualGroupCategoryColumn(): bool
    {
        if ($this->hasManualGroupCategoryColumn !== null) {
            return $this->hasManualGroupCategoryColumn;
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_categorypage_manual');
        $this->hasManualGroupCategoryColumn = (bool)$connection->tableColumnExists($table, 'group_category_id');

        return $this->hasManualGroupCategoryColumn;
    }

    public function getManualGroups(int $categoryId): array
    {
        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_categorypage_manual_groups');
        $rows = $connection->fetchAll(
            $connection->select()
                ->from($table)
                ->where('category_id = ?', $categoryId)
                ->order('sort_order ASC')
        );

        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->normalizeManualGroupRow($row);
        }

        return $result;
    }

    public function saveManualGroup(array $data): int
    {
        $categoryId = (int)($data['category_id'] ?? 0);
        $label = (string)($data['label'] ?? '');
        $sortOrder = (int)($data['sort_order'] ?? 0);
        $isEnabled = (int)($data['is_enabled'] ?? 1);

        if ($categoryId <= 0 ) {
            throw new \InvalidArgumentException('Invalid manual group data.');
        }

        $connection = $this->resource->getConnection();
        $table = $connection->getTableName('artm_categorypage_manual_groups');
        $entityId = (int)($data['entity_id'] ?? 0);

        if ($entityId > 0) {
            $connection->update($table, [
                'label' => $label,
                'sort_order' => $sortOrder,
                'is_enabled' => $isEnabled,
                'updated_at' => date('Y-m-d H:i:s'),
            ], ['entity_id = ?' => $entityId]);
            return $entityId;
        }

        $connection->insert($table, [
            'category_id' => $categoryId,
            'label' => $label,
            'sort_order' => $sortOrder,
            'is_enabled' => $isEnabled,
        ]);

        $this->cache->clean(['ARTM_CATEGORYPAGE']);
        return (int)$connection->lastInsertId();
    }

    public function deleteManualGroup(int $entityId): void
    {
        $connection = $this->resource->getConnection();
        $manualTable = $connection->getTableName('artm_categorypage_manual');
        $groupTable = $connection->getTableName('artm_categorypage_manual_groups');
        $connection->delete($manualTable, ['group_category_id = ?' => -$entityId]);
        $connection->delete($groupTable, ['entity_id = ?' => $entityId]);
        $this->cache->clean(['ARTM_CATEGORYPAGE']);
    }

    private function normalizeManualGroupRow(array $row): array
    {
        return [
            'entity_id' => (int)($row['entity_id'] ?? 0),
            'category_id' => (int)($row['category_id'] ?? 0),
            'label' => (string)($row['label'] ?? ''),
            'sort_order' => (int)($row['sort_order'] ?? 0),
            'is_enabled' => (int)($row['is_enabled'] ?? 1),
            'created_at' => (string)($row['created_at'] ?? ''),
            'updated_at' => (string)($row['updated_at'] ?? ''),
        ];
    }
}
