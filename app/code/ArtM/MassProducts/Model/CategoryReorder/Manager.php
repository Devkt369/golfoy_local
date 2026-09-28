<?php
namespace ArtM\MassProducts\Model\CategoryReorder;

use ArtM\MassProducts\Model\UploadFileReader;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Eav\Model\Config as EavConfig;

class Manager
{
    private const REQUIRED_HEADERS = ['sku', 'product_name', 'old_position', 'new_position', 'status', 'remarks'];

    private ResourceConnection $resource;
    private DirectoryList $directoryList;
    private File $fileDriver;
    private UploadFileReader $fileReader;
    private CategoryCollectionFactory $categoryCollectionFactory;
    private MetadataPool $metadataPool;
    private EavConfig $eavConfig;
    private DateTime $dateTime;

    public function __construct(
        ResourceConnection $resource,
        DirectoryList $directoryList,
        File $fileDriver,
        UploadFileReader $fileReader,
        CategoryCollectionFactory $categoryCollectionFactory,
        MetadataPool $metadataPool,
        EavConfig $eavConfig,
        DateTime $dateTime
    ) {
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->fileDriver = $fileDriver;
        $this->fileReader = $fileReader;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->metadataPool = $metadataPool;
        $this->eavConfig = $eavConfig;
        $this->dateTime = $dateTime;
    }

    public function getCategoryTree(): array
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active', 'include_in_menu'])
            ->addFieldToFilter('entity_id', ['neq' => 1])
            ->addFieldToFilter('level', ['gteq' => 1])
            ->setOrder('path', 'ASC');

        $categoryIds = [];
        foreach ($collection as $category) {
            $id = (int)$category->getId();
            if ($id > 0) {
                $categoryIds[] = $id;
            }
        }
        $productCounts = $this->getCategoryProductCounts($categoryIds);

        $nodesById = [];
        foreach ($collection as $category) {
            $id = (int)$category->getId();
            if ($id <= 0) {
                continue;
            }

            $nodesById[$id] = [
                'id' => $id,
                'name' => (string)$category->getName(),
                'level' => (int)$category->getLevel(),
                'parent_id' => (int)$category->getParentId(),
                'is_active' => (int)$category->getIsActive() === 1,
                'include_in_menu' => (int)$category->getIncludeInMenu() === 1,
                'product_count' => (int)($productCounts[$id] ?? 0),
                'children' => [],
            ];
        }

        foreach ($nodesById as $id => $node) {
            $parentId = (int)$node['parent_id'];
            if ($parentId > 0 && isset($nodesById[$parentId])) {
                $nodesById[$parentId]['children'][] = $id;
            }
        }

        $roots = [];
        foreach ($nodesById as $id => $node) {
            $parentId = (int)$node['parent_id'];
            if ($parentId <= 1 || !isset($nodesById[$parentId])) {
                $roots[] = $this->buildTreeNode($id, $nodesById);
            }
        }

        usort($roots, static function (array $a, array $b): int {
            return strcmp((string)$a['name'], (string)$b['name']);
        });

        return $roots;
    }

    public function searchCategories(string $term, int $limit = 50): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $collection = $this->categoryCollectionFactory->create();
        $collection->addAttributeToSelect(['name', 'is_active', 'include_in_menu'])
            ->addFieldToFilter('entity_id', ['neq' => 1])
            ->addFieldToFilter('level', ['gteq' => 1])
            ->addFieldToFilter('name', ['like' => '%' . $term . '%'])
            ->setPageSize(max(1, $limit))
            ->setCurPage(1)
            ->setOrder('name', 'ASC');

        $categoryIds = [];
        foreach ($collection as $category) {
            $id = (int)$category->getId();
            if ($id > 0) {
                $categoryIds[] = $id;
            }
        }
        $productCounts = $this->getCategoryProductCounts($categoryIds);

        $result = [];
        foreach ($collection as $category) {
            $id = (int)$category->getId();
            $result[] = [
                'id' => $id,
                'name' => (string)$category->getName(),
                'level' => (int)$category->getLevel(),
                'path' => (string)$category->getPath(),
                'is_active' => (int)$category->getIsActive() === 1,
                'include_in_menu' => (int)$category->getIncludeInMenu() === 1,
                'product_count' => (int)($productCounts[$id] ?? 0),
            ];
        }

        return $result;
    }

    public function getCategoryMeta(int $categoryId): array
    {
        $historyTable = $this->getHistoryTable();
        $connection = $this->resource->getConnection();

        $latest = $connection->fetchRow(
            $connection->select()
                ->from($historyTable)
                ->where('category_id = ?', $categoryId)
                ->order('entity_id DESC')
                ->limit(1)
        ) ?: [];

        $latestReordered = $connection->fetchRow(
            $connection->select()
                ->from($historyTable, ['uploaded_at', 'uploaded_by', 'updated_at'])
                ->where('category_id = ?', $categoryId)
                ->where('status IN (?)', ['applied', 'rollback_applied'])
                ->order('entity_id DESC')
                ->limit(1)
        ) ?: [];

        $lastUpdatedBy = (string)($latest['uploaded_by'] ?? $latest['downloaded_by'] ?? '');
        $lastReorderedDate = (string)($latestReordered['updated_at'] ?? $latestReordered['uploaded_at'] ?? '');

        return [
            'last_updated_by' => $lastUpdatedBy,
            'last_reordered_date' => $lastReorderedDate,
        ];
    }

    public function createDownloadSheet(int $categoryId, string $username, bool $skipConfigurableChildren = false): array
    {
        $rows = $this->getCategoryProductRows($categoryId);
        if ($skipConfigurableChildren) {
            $rows = $this->filterOutConfigurableChildren($rows);
        }

        if (empty($rows)) {
            throw new LocalizedException(__('No products found for this category with selected download option.'));
        }

        $version = $this->getNextVersion($categoryId);
        $baseFileName = 'category_' . $categoryId . '_v' . $version . '_' . date('Ymd_His');
        $sheetRows = [];
        foreach ($rows as $row) {
            $sheetRows[] = [
                'sku' => (string)$row['sku'],
                'product_name' => (string)$row['product_name'],
                'visibility' => $this->mapVisibilityLabel((int)($row['visibility'] ?? 0)),
                'enable_product' => ((int)($row['status'] ?? 0) === 1) ? 'yes' : 'no',
                'old_position' => (int)$row['position'],
                'new_position' => (int)$row['position'],
                'status' => 'pending',
                'remarks' => '',
            ];
        }

        $file = $this->writeSheet($baseFileName, $sheetRows);

        $historyId = $this->insertHistory(
            [
                'category_id' => $categoryId,
                'version' => $version,
                'file_name' => $file['name'],
                'file_path' => $file['relative_path'],
                'downloaded_at' => $this->dateTime->gmtDate('Y-m-d H:i:s'),
                'downloaded_by' => $username,
                'status' => 'downloaded',
                'total_products' => count($sheetRows),
                'success_count' => count($sheetRows),
                'failed_count' => 0,
                'remarks' => $skipConfigurableChildren
                    ? 'Generated latest category sheet (configurable children skipped)'
                    : 'Generated latest category sheet',
            ]
        );

        $this->insertHistoryItems($historyId, $categoryId, $sheetRows, $rows, 'snapshot');

        return [
            'history_id' => $historyId,
            'file_name' => $file['name'],
            'relative_path' => $file['relative_path'],
            'mime' => $file['mime'],
        ];
    }

    public function validateUploadAndCreateHistory(int $categoryId, array $uploadedFile, string $username): array
    {
        if (empty($uploadedFile['tmp_name']) || (int)($uploadedFile['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new LocalizedException(__('Invalid upload file.'));
        }

        $originalName = (string)($uploadedFile['name'] ?? '');
        $extension = strtolower((string)pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'csv'], true)) {
            throw new LocalizedException(__('Only XLSX and CSV files are supported.'));
        }

        $uploadDir = $this->getVarRelativeBasePath() . '/category_reorder/uploads';
        $absoluteUploadDir = $this->toAbsoluteVarPath($uploadDir);
        if (!$this->fileDriver->isExists($absoluteUploadDir)) {
            $this->fileDriver->createDirectory($absoluteUploadDir, 0755);
        }

        $savedName = 'upload_' . $categoryId . '_' . date('Ymd_His') . '.' . $extension;
        $absoluteSavedPath = $absoluteUploadDir . DIRECTORY_SEPARATOR . $savedName;
        $this->fileDriver->copy((string)$uploadedFile['tmp_name'], $absoluteSavedPath);

        $headers = $this->fileReader->extractHeaderColumns($absoluteSavedPath, $extension);
        $normalizedHeaderMap = [];
        foreach ($headers as $header) {
            $normalizedHeaderMap[$this->normalizeKey($header)] = $header;
        }

        $missingHeaders = [];
        foreach (self::REQUIRED_HEADERS as $requiredHeader) {
            if (!isset($normalizedHeaderMap[$requiredHeader])) {
                $missingHeaders[] = $requiredHeader;
            }
        }
        if (!empty($missingHeaders)) {
            throw new LocalizedException(__('Invalid sheet format. Missing headers: %1', implode(', ', $missingHeaders)));
        }

        $categoryRows = $this->getCategoryProductRows($categoryId);
        if (empty($categoryRows)) {
            throw new LocalizedException(__('Selected category has no products.'));
        }

        $categoryBySku = [];
        foreach ($categoryRows as $row) {
            $categoryBySku[$this->normalizeKey((string)$row['sku'])] = $row;
        }

        $fileRows = $this->fileReader->extractRows($absoluteSavedPath, $extension);
        $uploadedRows = [];
        $uploadedRowsBySku = [];
        $rowErrors = [];

        foreach ($fileRows as $entry) {
            $rowNumber = (int)($entry['row_number'] ?? 0);
            $data = is_array($entry['data'] ?? null) ? $entry['data'] : [];
            $mapped = [];
            foreach (self::REQUIRED_HEADERS as $header) {
                $sourceHeader = $normalizedHeaderMap[$header] ?? null;
                $mapped[$header] = $sourceHeader !== null ? trim((string)($data[$sourceHeader] ?? '')) : '';
            }

            $sku = $mapped['sku'];
            $skuKey = $this->normalizeKey($sku);
            if ($skuKey === '') {
                $rowErrors[$rowNumber][] = 'SKU is empty.';
                $uploadedRows[] = [
                    'row_number' => $rowNumber,
                    'sku' => $sku,
                    'sku_key' => $skuKey,
                    'product_name' => $mapped['product_name'],
                    'old_position' => $mapped['old_position'],
                    'new_position' => $mapped['new_position'],
                    'status' => $mapped['status'],
                    'remarks' => $mapped['remarks'],
                ];
                continue;
            }

            if (!isset($categoryBySku[$skuKey])) {
                $rowErrors[$rowNumber][] = 'SKU does not belong to selected category.';
            }

            if ($this->parseIntegerValue((string)$mapped['old_position']) === null) {
                $rowErrors[$rowNumber][] = 'old_position must be numeric.';
            }
            if ($this->parseIntegerValue((string)$mapped['new_position']) === null) {
                $rowErrors[$rowNumber][] = 'new_position must be numeric.';
            }

            if (isset($uploadedRowsBySku[$skuKey])) {
                $rowErrors[$rowNumber][] = 'Duplicate SKU row in uploaded file.';
                $existingRow = (int)$uploadedRowsBySku[$skuKey]['row_number'];
                $rowErrors[$existingRow][] = 'Duplicate SKU row in uploaded file.';
            }

            $uploadedRow = [
                'row_number' => $rowNumber,
                'sku' => $sku,
                'sku_key' => $skuKey,
                'product_name' => $mapped['product_name'],
                'old_position' => $mapped['old_position'],
                'new_position' => $mapped['new_position'],
                'status' => $mapped['status'],
                'remarks' => $mapped['remarks'],
            ];

            $uploadedRows[] = $uploadedRow;
            $uploadedRowsBySku[$skuKey] = $uploadedRow;
        }

        $missingSkus = [];
        foreach ($categoryBySku as $skuKey => $categoryRow) {
            if (!isset($uploadedRowsBySku[$skuKey])) {
                $missingSkus[] = (string)$categoryRow['sku'];
            }
        }

        $preview = [];
        $success = 0;
        $failed = 0;
        $skipped = 0;
        $matchedCategorySkus = [];

        foreach ($uploadedRows as $uploaded) {
            $sku = (string)$uploaded['sku'];
            $skuKey = (string)$uploaded['sku_key'];
            $rowNumber = (int)$uploaded['row_number'];
            $categoryRow = $skuKey !== '' ? ($categoryBySku[$skuKey] ?? null) : null;
            $oldPositionFromSheet = $this->parseIntegerValue((string)$uploaded['old_position']);
            $newPositionFromSheet = $this->parseIntegerValue((string)$uploaded['new_position']);
            $oldPosition = $categoryRow ? (int)$categoryRow['position'] : ($oldPositionFromSheet ?? 0);
            $productName = $categoryRow ? (string)$categoryRow['product_name'] : (string)$uploaded['product_name'];
            $errors = $rowErrors[$rowNumber] ?? [];

            if ($categoryRow) {
                $matchedCategorySkus[$skuKey] = true;
            }

            if ($categoryRow && empty($errors) && $oldPositionFromSheet !== null && $oldPositionFromSheet !== $oldPosition) {
                $errors[] = 'old_position mismatch with current category data.';
            }

            if (!empty($errors)) {
                $failed++;
                $preview[] = [
                    'sku' => $sku,
                    'product_name' => $productName,
                    'old_position' => $oldPosition,
                    'new_position' => $newPositionFromSheet ?? $oldPosition,
                    'status' => 'failed',
                    'remarks' => implode(' ', array_unique($errors)),
                ];
                continue;
            }

            $newPosition = $newPositionFromSheet ?? $oldPosition;
            if ($newPosition === $oldPosition) {
                $skipped++;
                $preview[] = [
                    'sku' => $sku,
                    'product_name' => $productName,
                    'old_position' => $oldPosition,
                    'new_position' => $newPosition,
                    'status' => 'skipped',
                    'remarks' => 'No position change.',
                ];
                continue;
            }

            $success++;
            $preview[] = [
                'sku' => $sku,
                'product_name' => $productName,
                'old_position' => $oldPosition,
                'new_position' => $newPosition,
                'status' => 'success',
                'remarks' => (string)$uploaded['remarks'],
            ];
        }

        foreach ($categoryRows as $categoryRow) {
            $sku = (string)$categoryRow['sku'];
            $skuKey = $this->normalizeKey($sku);
            if (isset($matchedCategorySkus[$skuKey])) {
                continue;
            }

            $failed++;
            $preview[] = [
                'sku' => $sku,
                'product_name' => (string)$categoryRow['product_name'],
                'old_position' => (int)$categoryRow['position'],
                'new_position' => (int)$categoryRow['position'],
                'status' => 'failed',
                'remarks' => 'Missing row in uploaded file.',
            ];
        }

        $version = $this->getNextVersion($categoryId);
        $historyId = $this->insertHistory([
            'category_id' => $categoryId,
            'version' => $version,
            'file_name' => $savedName,
            'file_path' => $uploadDir . '/' . $savedName,
            'uploaded_at' => $this->dateTime->gmtDate('Y-m-d H:i:s'),
            'uploaded_by' => $username,
            'status' => 'validated',
            'total_products' => count($preview),
            'success_count' => $success,
            'failed_count' => $failed,
            'remarks' => 'Validation complete. Confirm reorder to apply changes.',
        ]);

        $this->insertHistoryItems($historyId, $categoryId, $preview, $categoryRows, 'validated');

        $globalErrors = [];
        if (!empty($missingSkus)) {
            $globalErrors[] = 'Missing required rows for SKUs: ' . implode(', ', array_slice($missingSkus, 0, 25));
        }

        return [
            'history_id' => $historyId,
            'version' => $version,
            'summary' => [
                'total' => count($preview),
                'success' => $success,
                'failed' => $failed,
                'skipped' => $skipped,
            ],
            'errors' => $globalErrors,
            'preview' => $preview,
        ];
    }

    public function applyHistory(int $historyId, int $categoryId, string $username): array
    {
        $connection = $this->resource->getConnection();
        $historyTable = $this->getHistoryTable();
        $itemTable = $this->getItemTable();
        $ccpTable = $connection->getTableName('catalog_category_product');

        $history = $connection->fetchRow(
            $connection->select()->from($historyTable)->where('entity_id = ?', $historyId)
        );
        if (!$history) {
            throw new LocalizedException(__('History record not found.'));
        }
        if ((int)$history['category_id'] !== $categoryId) {
            throw new LocalizedException(__('History does not belong to selected category.'));
        }

        $items = $connection->fetchAll(
            $connection->select()->from($itemTable)->where('history_id = ?', $historyId)
        );

        $skuToProduct = [];
        foreach ($this->getCategoryProductRows($categoryId) as $row) {
            $skuToProduct[$this->normalizeKey((string)$row['sku'])] = (int)$row['product_id'];
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;

        $connection->beginTransaction();
        try {
            foreach ($items as $item) {
                $itemId = (int)$item['item_id'];
                $rowStatus = (string)$item['row_status'];
                if ($rowStatus === 'skipped') {
                    $skipped++;
                    continue;
                }
                if ($rowStatus !== 'success') {
                    $failed++;
                    continue;
                }

                $skuKey = $this->normalizeKey((string)$item['sku']);
                $productId = (int)($skuToProduct[$skuKey] ?? 0);
                if ($productId <= 0) {
                    $failed++;
                    $connection->update(
                        $itemTable,
                        ['row_status' => 'failed', 'remarks' => 'Product no longer in this category.'],
                        ['item_id = ?' => $itemId]
                    );
                    continue;
                }

                $connection->update(
                    $ccpTable,
                    ['position' => (int)$item['new_position']],
                    ['category_id = ?' => $categoryId, 'product_id = ?' => $productId]
                );
                $success++;
            }

            $connection->update(
                $historyTable,
                [
                    'status' => 'applied',
                    'success_count' => $success,
                    'failed_count' => $failed,
                    'remarks' => 'Applied by ' . $username . ' at ' . $this->dateTime->gmtDate('Y-m-d H:i:s'),
                ],
                ['entity_id = ?' => $historyId]
            );

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        return [
            'history_id' => $historyId,
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    public function rollbackVersion(int $categoryId, int $targetHistoryId, string $username): array
    {
        $connection = $this->resource->getConnection();
        $historyTable = $this->getHistoryTable();
        $itemTable = $this->getItemTable();

        $target = $connection->fetchRow(
            $connection->select()->from($historyTable)->where('entity_id = ?', $targetHistoryId)
        );
        if (!$target || (int)$target['category_id'] !== $categoryId) {
            throw new LocalizedException(__('Invalid target history version.'));
        }

        $targetItems = $connection->fetchAll(
            $connection->select()
                ->from($itemTable)
                ->where('history_id = ?', $targetHistoryId)
        );

        if (empty($targetItems)) {
            throw new LocalizedException(__('No rollback data found for selected version.'));
        }

        $targetOldPositions = [];
        foreach ($targetItems as $item) {
            $targetOldPositions[$this->normalizeKey((string)$item['sku'])] = (int)$item['old_position'];
        }

        $currentRows = $this->getCategoryProductRows($categoryId);
        $preview = [];
        foreach ($currentRows as $row) {
            $sku = (string)$row['sku'];
            $skuKey = $this->normalizeKey($sku);
            if (!isset($targetOldPositions[$skuKey])) {
                continue;
            }

            $oldPos = (int)$row['position'];
            $newPos = (int)$targetOldPositions[$skuKey];
            $status = $newPos === $oldPos ? 'skipped' : 'success';

            $preview[] = [
                'sku' => $sku,
                'product_name' => (string)$row['product_name'],
                'old_position' => $oldPos,
                'new_position' => $newPos,
                'status' => $status,
                'remarks' => 'Rollback from version ' . (int)$target['version'],
            ];
        }

        $version = $this->getNextVersion($categoryId);
        $historyId = $this->insertHistory([
            'category_id' => $categoryId,
            'version' => $version,
            'status' => 'rollback_pending',
            'uploaded_at' => $this->dateTime->gmtDate('Y-m-d H:i:s'),
            'uploaded_by' => $username,
            'total_products' => count($preview),
            'success_count' => 0,
            'failed_count' => 0,
            'remarks' => 'Rollback requested to version ' . (int)$target['version'],
        ]);

        $this->insertHistoryItems($historyId, $categoryId, $preview, $currentRows, 'rollback');
        $applied = $this->applyRollbackHistory($historyId, $categoryId, $username);

        return [
            'history_id' => $historyId,
            'version' => $version,
            'target_version' => (int)$target['version'],
            'summary' => $applied,
        ];
    }

    public function getHistory(int $categoryId, int $page = 1, int $pageSize = 20): array
    {
        $page = max(1, $page);
        $pageSize = max(1, min(100, $pageSize));
        $offset = ($page - 1) * $pageSize;

        $connection = $this->resource->getConnection();
        $historyTable = $this->getHistoryTable();

        $total = (int)$connection->fetchOne(
            $connection->select()->from($historyTable, ['cnt' => 'COUNT(*)'])->where('category_id = ?', $categoryId)
        );

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($historyTable)
                ->where('category_id = ?', $categoryId)
                ->order('entity_id DESC')
                ->limit($pageSize, $offset)
        );

        return [
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'items' => array_map(static function (array $row): array {
                return [
                    'history_id' => (int)$row['entity_id'],
                    'version' => (int)$row['version'],
                    'file_name' => (string)($row['file_name'] ?? ''),
                    'uploaded_by' => (string)($row['uploaded_by'] ?? $row['downloaded_by'] ?? ''),
                    'uploaded_at' => (string)($row['uploaded_at'] ?? $row['downloaded_at'] ?? ''),
                    'affected_products' => (int)($row['total_products'] ?? 0),
                    'success_count' => (int)($row['success_count'] ?? 0),
                    'failed_count' => (int)($row['failed_count'] ?? 0),
                    'status' => (string)($row['status'] ?? ''),
                    'remarks' => (string)($row['remarks'] ?? ''),
                    'can_download' => !empty($row['file_path']),
                ];
            }, $rows),
        ];
    }

    public function getHistoryFile(int $historyId): array
    {
        $connection = $this->resource->getConnection();
        $historyTable = $this->getHistoryTable();

        $row = $connection->fetchRow(
            $connection->select()->from($historyTable)->where('entity_id = ?', $historyId)
        );
        if (!$row || empty($row['file_path']) || empty($row['file_name'])) {
            throw new LocalizedException(__('History file not available.'));
        }

        $relativePath = (string)$row['file_path'];
        $absolutePath = $this->toAbsoluteVarPath($relativePath);
        if (!is_file($absolutePath)) {
            throw new LocalizedException(__('History file missing from storage.'));
        }

        return [
            'name' => (string)$row['file_name'],
            'relative_path' => $relativePath,
        ];
    }

    private function applyRollbackHistory(int $historyId, int $categoryId, string $username): array
    {
        $connection = $this->resource->getConnection();
        $itemTable = $this->getItemTable();
        $historyTable = $this->getHistoryTable();
        $ccpTable = $connection->getTableName('catalog_category_product');

        $items = $connection->fetchAll(
            $connection->select()->from($itemTable)->where('history_id = ?', $historyId)
        );

        $skuToProduct = [];
        foreach ($this->getCategoryProductRows($categoryId) as $row) {
            $skuToProduct[$this->normalizeKey((string)$row['sku'])] = (int)$row['product_id'];
        }

        $success = 0;
        $failed = 0;
        $skipped = 0;

        $connection->beginTransaction();
        try {
            foreach ($items as $item) {
                $itemId = (int)$item['item_id'];
                if ((string)$item['row_status'] === 'skipped') {
                    $skipped++;
                    continue;
                }

                $skuKey = $this->normalizeKey((string)$item['sku']);
                $productId = (int)($skuToProduct[$skuKey] ?? 0);
                if ($productId <= 0) {
                    $failed++;
                    $connection->update(
                        $itemTable,
                        ['row_status' => 'failed', 'remarks' => 'Product no longer in selected category.'],
                        ['item_id = ?' => $itemId]
                    );
                    continue;
                }

                $connection->update(
                    $ccpTable,
                    ['position' => (int)$item['new_position']],
                    ['category_id = ?' => $categoryId, 'product_id = ?' => $productId]
                );
                $success++;
            }

            $existingRemarks = (string)$connection->fetchOne(
                $connection->select()
                    ->from($historyTable, ['remarks'])
                    ->where('entity_id = ?', $historyId)
                    ->limit(1)
            );

            $connection->update(
                $historyTable,
                [
                    'status' => 'rollback_applied',
                    'success_count' => $success,
                    'failed_count' => $failed,
                    'remarks' => trim($existingRemarks . ' | Applied by ' . $username . ' at ' . $this->dateTime->gmtDate('Y-m-d H:i:s'), ' |'),
                ],
                ['entity_id = ?' => $historyId]
            );

            $connection->commit();
        } catch (\Throwable $e) {
            $connection->rollBack();
            throw $e;
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'skipped' => $skipped,
        ];
    }

    private function getCategoryProductRows(int $categoryId): array
    {
        $connection = $this->resource->getConnection();
        $ccpTable = $connection->getTableName('catalog_category_product');
        $cpeTable = $connection->getTableName('catalog_product_entity');
        $varcharTable = $connection->getTableName('catalog_product_entity_varchar');
        $intTable = $connection->getTableName('catalog_product_entity_int');

        $nameAttributeId = (int)$this->eavConfig->getAttribute('catalog_product', 'name')->getId();
        $visibilityAttributeId = (int)$this->eavConfig->getAttribute('catalog_product', 'visibility')->getId();
        $statusAttributeId = (int)$this->eavConfig->getAttribute('catalog_product', 'status')->getId();
        $metadata = $this->metadataPool->getMetadata(ProductInterface::class);
        $linkField = (string)$metadata->getLinkField();

        $select = $connection->select()
            ->from(['ccp' => $ccpTable], ['product_id' => 'product_id', 'position' => 'position'])
            ->joinInner(['cpe' => $cpeTable], 'cpe.entity_id = ccp.product_id', ['sku' => 'sku'])
            ->joinLeft(
                ['name' => $varcharTable],
                'name.' . $linkField . ' = cpe.' . $linkField
                . ' AND name.attribute_id = ' . $nameAttributeId
                . ' AND name.store_id = 0',
                ['product_name' => new \Zend_Db_Expr('COALESCE(name.value, cpe.sku)')]
            )
            ->joinLeft(
                ['visibility_attr' => $intTable],
                'visibility_attr.' . $linkField . ' = cpe.' . $linkField
                . ' AND visibility_attr.attribute_id = ' . $visibilityAttributeId
                . ' AND visibility_attr.store_id = 0',
                ['visibility' => new \Zend_Db_Expr('COALESCE(visibility_attr.value, 1)')]
            )
            ->joinLeft(
                ['status_attr' => $intTable],
                'status_attr.' . $linkField . ' = cpe.' . $linkField
                . ' AND status_attr.attribute_id = ' . $statusAttributeId
                . ' AND status_attr.store_id = 0',
                ['status' => new \Zend_Db_Expr('COALESCE(status_attr.value, 2)')]
            )
            ->where('ccp.category_id = ?', $categoryId)
            ->order('ccp.position ASC')
            ->order('cpe.sku ASC');

        return $connection->fetchAll($select);
    }

    private function filterOutConfigurableChildren(array $rows): array
    {
        if (empty($rows)) {
            return $rows;
        }

        $productIds = [];
        foreach ($rows as $row) {
            $productId = (int)($row['product_id'] ?? 0);
            if ($productId > 0) {
                $productIds[] = $productId;
            }
        }

        $configurableChildIds = $this->getConfigurableChildProductIds($productIds);
        if (empty($configurableChildIds)) {
            return $rows;
        }

        $filtered = [];
        foreach ($rows as $row) {
            $productId = (int)($row['product_id'] ?? 0);
            if ($productId > 0 && isset($configurableChildIds[$productId])) {
                continue;
            }
            $filtered[] = $row;
        }

        return $filtered;
    }

    private function getConfigurableChildProductIds(array $productIds): array
    {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));
        if (empty($productIds)) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $superLinkTable = $connection->getTableName('catalog_product_super_link');

        $childIds = $connection->fetchCol(
            $connection->select()
                ->from($superLinkTable, ['product_id'])
                ->where('product_id IN (?)', $productIds)
        );

        $result = [];
        foreach ($childIds as $childId) {
            $id = (int)$childId;
            if ($id > 0) {
                $result[$id] = true;
            }
        }

        return $result;
    }

    private function writeSheet(string $baseFileName, array $rows): array
    {
        $downloadDir = $this->getVarRelativeBasePath() . '/category_reorder/downloads';
        $absoluteDownloadDir = $this->toAbsoluteVarPath($downloadDir);
        if (!$this->fileDriver->isExists($absoluteDownloadDir)) {
            $this->fileDriver->createDirectory($absoluteDownloadDir, 0755);
        }

        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            $filename = $baseFileName . '.xlsx';
            $relativePath = $downloadDir . '/' . $filename;
            $absolutePath = $this->toAbsoluteVarPath($relativePath);

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $headers = ['sku', 'product_name', 'visibility', 'enable_product', 'old_position', 'new_position', 'status', 'remarks'];

            foreach ($headers as $i => $header) {
                $sheet->setCellValueByColumnAndRow($i + 1, 1, $header);
            }
            $rowIndex = 2;
            foreach ($rows as $row) {
                $sheet->setCellValueByColumnAndRow(1, $rowIndex, (string)$row['sku']);
                $sheet->setCellValueByColumnAndRow(2, $rowIndex, (string)$row['product_name']);
                $sheet->setCellValueByColumnAndRow(3, $rowIndex, (string)($row['visibility'] ?? 'no'));
                $sheet->setCellValueByColumnAndRow(4, $rowIndex, (string)($row['enable_product'] ?? 'no'));
                $sheet->setCellValueByColumnAndRow(5, $rowIndex, (int)$row['old_position']);
                $sheet->setCellValueByColumnAndRow(6, $rowIndex, (int)$row['new_position']);
                $sheet->setCellValueByColumnAndRow(7, $rowIndex, (string)$row['status']);
                $sheet->setCellValueByColumnAndRow(8, $rowIndex, (string)$row['remarks']);
                $rowIndex++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($absolutePath);

            return [
                'name' => $filename,
                'relative_path' => $relativePath,
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ];
        }

        $filename = $baseFileName . '.csv';
        $relativePath = $downloadDir . '/' . $filename;
        $absolutePath = $this->toAbsoluteVarPath($relativePath);

        $handle = fopen($absolutePath, 'w');
        if ($handle === false) {
            throw new LocalizedException(__('Unable to write CSV file.'));
        }

        fputcsv($handle, ['sku', 'product_name', 'visibility', 'enable_product', 'old_position', 'new_position', 'status', 'remarks']);
        foreach ($rows as $row) {
            fputcsv($handle, [
                (string)$row['sku'],
                (string)$row['product_name'],
                (string)($row['visibility'] ?? 'no'),
                (string)($row['enable_product'] ?? 'no'),
                (int)$row['old_position'],
                (int)$row['new_position'],
                (string)$row['status'],
                (string)$row['remarks'],
            ]);
        }
        fclose($handle);

        return [
            'name' => $filename,
            'relative_path' => $relativePath,
            'mime' => 'text/csv',
        ];
    }

    private function insertHistory(array $data): int
    {
        $connection = $this->resource->getConnection();
        $table = $this->getHistoryTable();
        $connection->insert($table, $data);

        return (int)$connection->lastInsertId($table);
    }

    private function insertHistoryItems(
        int $historyId,
        int $categoryId,
        array $rows,
        array $categoryRows,
        string $defaultStatus
    ): void {
        $connection = $this->resource->getConnection();
        $itemTable = $this->getItemTable();

        $categoryBySku = [];
        foreach ($categoryRows as $categoryRow) {
            $categoryBySku[$this->normalizeKey((string)$categoryRow['sku'])] = $categoryRow;
        }

        $insertRows = [];
        foreach ($rows as $row) {
            $sku = (string)($row['sku'] ?? '');
            $skuKey = $this->normalizeKey($sku);
            $categoryRow = $categoryBySku[$skuKey] ?? [];

            $insertRows[] = [
                'history_id' => $historyId,
                'category_id' => $categoryId,
                'product_id' => (int)($categoryRow['product_id'] ?? 0),
                'sku' => $sku,
                'product_name' => (string)($row['product_name'] ?? ($categoryRow['product_name'] ?? '')),
                'old_position' => (int)($row['old_position'] ?? ($categoryRow['position'] ?? 0)),
                'new_position' => (int)($row['new_position'] ?? ($categoryRow['position'] ?? 0)),
                'row_status' => (string)($row['status'] ?? $defaultStatus),
                'remarks' => (string)($row['remarks'] ?? ''),
            ];
        }

        if (!empty($insertRows)) {
            $connection->insertMultiple($itemTable, $insertRows);
        }
    }

    /**
     * @param int $nodeId
     * @param array<int, array<string, mixed>> $nodesById
     * @return array<string, mixed>
     */
    private function buildTreeNode(int $nodeId, array $nodesById): array
    {
        if (!isset($nodesById[$nodeId])) {
            return [];
        }

        $node = $nodesById[$nodeId];
        $childIds = is_array($node['children'] ?? null) ? $node['children'] : [];
        $children = [];

        foreach ($childIds as $childId) {
            $childId = (int)$childId;
            if (!isset($nodesById[$childId])) {
                continue;
            }
            $children[] = $this->buildTreeNode($childId, $nodesById);
        }

        usort($children, static function (array $a, array $b): int {
            return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });

        return [
            'id' => (int)$node['id'],
            'name' => (string)$node['name'],
            'level' => (int)$node['level'],
            'parent_id' => (int)$node['parent_id'],
            'is_active' => (bool)$node['is_active'],
            'include_in_menu' => (bool)($node['include_in_menu'] ?? false),
            'product_count' => (int)($node['product_count'] ?? 0),
            'children' => $children,
        ];
    }

    /**
     * @param array<int, int> $categoryIds
     * @return array<int, int>
     */
    private function getCategoryProductCounts(array $categoryIds): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $connection = $this->resource->getConnection();
        $ccpTable = $connection->getTableName('catalog_category_product');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($ccpTable, ['category_id', 'cnt' => 'COUNT(product_id)'])
                ->where('category_id IN (?)', $categoryIds)
                ->group('category_id')
        );

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int)$row['category_id']] = (int)$row['cnt'];
        }

        return $counts;
    }

    private function getNextVersion(int $categoryId): int
    {
        $connection = $this->resource->getConnection();
        $historyTable = $this->getHistoryTable();

        $maxVersion = (int)$connection->fetchOne(
            $connection->select()->from($historyTable, ['max_ver' => 'MAX(version)'])->where('category_id = ?', $categoryId)
        );

        return $maxVersion + 1;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $value)));
    }

    private function parseIntegerValue(string $value): ?int
    {
        $value = trim($value);
        if ($value === '' || !preg_match('/^-?\d+$/', $value)) {
            return null;
        }

        return (int)$value;
    }

    private function mapVisibilityLabel(int $visibility): string
    {
        switch ($visibility) {
            case 1:
                return 'Not Visible Individually';
            case 2:
                return 'Catalog';
            case 3:
                return 'Search';
            case 4:
                return 'Catalog, Search';
            default:
                return 'Not Visible Individually';
        }
    }

    private function getHistoryTable(): string
    {
        return $this->resource->getConnection()->getTableName('artm_category_reorder_history');
    }

    private function getItemTable(): string
    {
        return $this->resource->getConnection()->getTableName('artm_category_reorder_item');
    }

    private function getVarRelativeBasePath(): string
    {
        return 'artm_massproducts';
    }

    private function toAbsoluteVarPath(string $relativeVarPath): string
    {
        $varDir = rtrim($this->directoryList->getPath(DirectoryList::VAR_DIR), DIRECTORY_SEPARATOR);

        return $varDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativeVarPath);
    }
}
