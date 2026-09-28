<?php
namespace ArtM\MassProducts\Cron;

use ArtM\MassProducts\Model\Logger\UploadLogger;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ResourceConnection;

class ProcessQueuedProducts
{
    private const XML_PATH_ENABLE = 'artm_massproducts/cron/enable';
    private const XML_PATH_MODE = 'artm_massproducts/cron/mode';
    private const XML_PATH_START_DATETIME = 'artm_massproducts/cron/start_datetime';
    private const XML_PATH_INTERVAL = 'artm_massproducts/cron/interval';
    private const XML_PATH_INTERVAL_VALUE = 'artm_massproducts/cron/interval_value';
    private const XML_PATH_BATCH_SIZE = 'artm_massproducts/cron/batch_size';
    private const XML_PATH_STOP_AFTER_COMPLETE = 'artm_massproducts/cron/stop_after_complete';
    private const XML_PATH_NEXT_RUN_AT = 'artm_massproducts/cron/next_run_at';

    private $resource;
    private $productFactory;
    private $productRepository;
    private $scopeConfig;
    private $configWriter;
    private $uploadLogger;
    private $draftColumns = null;

    public function __construct(
        ResourceConnection $resource,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        WriterInterface $configWriter,
        UploadLogger $uploadLogger
    ) {
        $this->resource = $resource;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->scopeConfig = $scopeConfig;
        $this->configWriter = $configWriter;
        $this->uploadLogger = $uploadLogger;
    }

    private function getConfig(string $path)
    {
        return $this->scopeConfig->getValue($path, ScopeConfigInterface::SCOPE_TYPE_DEFAULT, 0);
    }

    private function isCronEnabled(): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_ENABLE);
    }

    private function getMode(): string
    {
        $mode = trim((string)$this->getConfig(self::XML_PATH_MODE));
        return $mode !== '' ? $mode : 'run_once';
    }

    private function getStartTime(): string
    {
        return trim((string)$this->getConfig(self::XML_PATH_START_DATETIME));
    }

    private function getInterval(): string
    {
        return trim((string)$this->getConfig(self::XML_PATH_INTERVAL));
    }

    private function getIntervalValue(): int
    {
        return (int)$this->getConfig(self::XML_PATH_INTERVAL_VALUE);
    }

    private function getBatchSize(): int
    {
        $size = (int)$this->getConfig(self::XML_PATH_BATCH_SIZE);
        return $size > 0 ? $size : 20;
    }

    private function getNextRunAt(): string
    {
        return trim((string)$this->getConfig(self::XML_PATH_NEXT_RUN_AT));
    }

    private function shouldStopAfterComplete(): bool
    {
        return (bool)$this->getConfig(self::XML_PATH_STOP_AFTER_COMPLETE);
    }

    private function getDraftColumns(): array
    {
        if (is_array($this->draftColumns)) {
            return $this->draftColumns;
        }

        $connection = $this->resource->getConnection();
        $draftTable = $connection->getTableName('artm_massproducts_draft');
        $description = $connection->describeTable($draftTable);

        $this->draftColumns = array_keys($description);

        return $this->draftColumns;
    }

    private function prepareDraftUpdate(array $data): array
    {
        $columns = array_flip($this->getDraftColumns());
        $filtered = [];

        foreach ($data as $key => $value) {
            if (isset($columns[$key])) {
                $filtered[$key] = $value;
            }
        }

        return $filtered;
    }

    private function resolveDraftAttributeData(int $draftId, array $baseData): array
    {
        $connection = $this->resource->getConnection();
        $valueTable = $connection->getTableName('artm_massproducts_draft_values');
        if (!$connection->isTableExists($valueTable)) {
            return $baseData;
        }

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($valueTable, ['attribute_code', 'value'])
                ->where('draft_id = ?', $draftId)
                ->where('store_id = ?', 0)
        );

        foreach ($rows as $row) {
            $code = trim((string)($row['attribute_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $baseData[$code] = $row['value'];
        }

        return $baseData;
    }

    private function extractCategoryIds(array &$data): array
    {
        $keys = ['category_ids', 'categories', 'category'];
        $raw = '';

        foreach ($keys as $key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }

            $value = $data[$key];
            unset($data[$key]);

            if (is_array($value)) {
                $raw = implode(',', $value);
            } else {
                $raw = (string)$value;
            }

            break;
        }

        if ($raw === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', array_map('trim', explode(',', $raw))), function ($id) {
            return $id > 0;
        })));
    }

    private function extractMsiQuantities(array &$data): array
    {
        $out = [];
        foreach ($data as $code => $value) {
            $attrCode = (string)$code;
            if (strpos($attrCode, 'msi_qty__') !== 0) {
                continue;
            }

            $sourceCode = substr($attrCode, 9);
            unset($data[$code]);
            if ($sourceCode === '') {
                continue;
            }

            $qtyRaw = trim((string)$value);
            if ($qtyRaw === '' || !is_numeric($qtyRaw)) {
                continue;
            }

            $out[$sourceCode] = (float)$qtyRaw;
        }

        return $out;
    }

    private function saveMsiSourceItems(string $sku, array $sourceQtyMap): void
    {
        if ($sku === '' || empty($sourceQtyMap)) {
            return;
        }

        $sourceItemsSaveClass = 'Magento\\InventoryApi\\Api\\SourceItemsSaveInterface';
        $sourceItemFactoryClass = 'Magento\\InventoryApi\\Api\\Data\\SourceItemInterfaceFactory';
        if (!interface_exists($sourceItemsSaveClass) || !class_exists($sourceItemFactoryClass)) {
            return;
        }

        $om = ObjectManager::getInstance();
        $sourceItemsSave = $om->get($sourceItemsSaveClass);
        $sourceItemFactory = $om->get($sourceItemFactoryClass);

        $items = [];
        foreach ($sourceQtyMap as $sourceCode => $qty) {
            $source = trim((string)$sourceCode);
            if ($source === '') {
                continue;
            }

            $item = $sourceItemFactory->create();
            $item->setSourceCode($source);
            $item->setSku($sku);
            $item->setQuantity((float)$qty);
            $item->setStatus(((float)$qty) > 0 ? 1 : 0);
            $items[] = $item;
        }

        if (!empty($items)) {
            $sourceItemsSave->execute($items);
        }
    }

    private function updateLegacyStock(string $sku, array $sourceQtyMap, string $productTypeId = 'simple'): void
    {
        if ($sku === '') {
            return;
        }

        $stockRegistryClass = 'Magento\\CatalogInventory\\Api\\StockRegistryInterface';
        if (!interface_exists($stockRegistryClass)) {
            return;
        }

        $qty = 0.0;
        foreach ($sourceQtyMap as $sourceQty) {
            $qty += (float)$sourceQty;
        }

        $om = ObjectManager::getInstance();
        $stockRegistry = $om->get($stockRegistryClass);
        $stockItem = $stockRegistry->getStockItemBySku($sku);
        if (!$stockItem) {
            return;
        }

        if (empty($sourceQtyMap)) {
            $stockItem->setQty($qty);
        }
        $stockItem->setIsInStock($qty > 0 ? 1 : 0);
        $stockItem->setManageStock($productTypeId === 'configurable' ? 0 : 1);
        $stockRegistry->updateStockItemBySku($sku, $stockItem);
    }

    private function applyDraftMediaToProduct($product, int $draftId): array
    {
        $report = [
            'draft_id' => $draftId,
            'total_rows' => 0,
            'attached' => 0,
            'skipped_missing' => 0,
            'skipped_extension' => 0,
            'errors' => [],
        ];

        if ($draftId <= 0 || !$product) {
            return $report;
        }

        $connection = $this->resource->getConnection();
        $mediaTable = $connection->getTableName('artm_massproducts_draft_media');
        if (!$connection->isTableExists($mediaTable)) {
            return $report;
        }

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($mediaTable, ['file_path', 'roles', 'position'])
                ->where('draft_id = ?', $draftId)
                ->order('position ASC')
                ->order('media_id ASC')
        );
        $report['total_rows'] = count($rows);
        if (empty($rows)) {
            return $report;
        }

        $om = ObjectManager::getInstance();
        $filesystem = $om->get('Magento\\Framework\\Filesystem');
        $mediaDir = $filesystem->getDirectoryRead(DirectoryList::MEDIA);
        $baseAssigned = false;
        $fallbackBaseNeeded = false;
        $validImageSeen = false;

        foreach ($rows as $row) {
            $relPath = ltrim((string)($row['file_path'] ?? ''), '/');
            if ($relPath === '' || !$mediaDir->isExist($relPath)) {
                $report['skipped_missing']++;
                continue;
            }

            $ext = strtolower((string)pathinfo($relPath, PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                $roles = array_values(array_filter(array_map('trim', explode(',', (string)($row['roles'] ?? '')))));
                if (in_array('base', $roles, true)) {
                    // Legacy base image may still be webp/gif from older uploads; fallback base will be assigned later.
                    $fallbackBaseNeeded = true;
                }
                $report['skipped_extension']++;
                continue;
            }

            $absPath = $mediaDir->getAbsolutePath($relPath);
            $roles = array_values(array_filter(array_map('trim', explode(',', (string)($row['roles'] ?? '')))));
            $types = [];
            if (in_array('base', $roles, true)) {
                $types[] = 'image';
                $baseAssigned = true;
            }
            if (in_array('small', $roles, true)) {
                $types[] = 'small_image';
            }
            if (in_array('thumbnail', $roles, true)) {
                $types[] = 'thumbnail';
            }

            if (!$baseAssigned && $fallbackBaseNeeded && !$validImageSeen) {
                $types[] = 'image';
                $baseAssigned = true;
            }

            try {
                $product->addImageToMediaGallery($absPath, !empty($types) ? $types : null, false, false);
                $validImageSeen = true;
                $report['attached']++;
            } catch (\Throwable $e) {
                $report['errors'][] = [
                    'file_path' => $relPath,
                    'message' => $e->getMessage(),
                ];
                if (count($report['errors']) > 5) {
                    $report['errors'] = array_slice($report['errors'], 0, 5);
                }
                continue;
            }
        }

        return $report;
    }

    private function sanitizeImageAttributesBeforeSave($product): void
    {
        if (!$product) {
            return;
        }

        $validFiles = [];
        $gallery = $product->getMediaGallery('images');
        if (is_array($gallery)) {
            foreach ($gallery as $item) {
                $file = (string)($item['file'] ?? '');
                if ($file === '') {
                    continue;
                }
                $normalized = '/' . ltrim($file, '/');
                $validFiles[$normalized] = true;
                $validFiles[ltrim($normalized, '/')] = true;
            }
        }

        $om = ObjectManager::getInstance();
        $filesystem = $om->get('Magento\\Framework\\Filesystem');
        $mediaDir = $filesystem->getDirectoryRead(DirectoryList::MEDIA);

        $isValidImageValue = function ($value) use ($validFiles, $mediaDir): bool {
            $val = trim((string)$value);
            if ($val === '' || $val === 'no_selection') {
                return true;
            }

            $normalized = '/' . ltrim($val, '/');
            if (isset($validFiles[$normalized]) || isset($validFiles[ltrim($normalized, '/')])) {
                return true;
            }

            $path = ltrim($normalized, '/');
            if (strpos($path, 'catalog/product/') === 0 && $mediaDir->isExist($path)) {
                return true;
            }

            return false;
        };

        foreach (['image', 'small_image', 'thumbnail', 'swatch_image', 'swatch_thumbnail'] as $coreCode) {
            $val = $product->getData($coreCode);
            if (!$isValidImageValue($val)) {
                $product->setData($coreCode, 'no_selection');
            }
        }

        foreach ((array)$product->getAttributes() as $attribute) {
            if (!$attribute || (string)$attribute->getFrontendInput() !== 'media_image') {
                continue;
            }
            $code = (string)$attribute->getAttributeCode();
            if ($code === '') {
                continue;
            }
            $val = $product->getData($code);
            if (!$isValidImageValue($val)) {
                $product->setData($code, 'no_selection');
            }
        }
    }

    private function forceClearAllImageAttributes($product): void
    {
        if (!$product) {
            return;
        }

        foreach (['image', 'small_image', 'thumbnail', 'swatch_image', 'swatch_thumbnail'] as $coreCode) {
            $product->setData($coreCode, 'no_selection');
        }

        foreach ((array)$product->getAttributes() as $attribute) {
            if (!$attribute || (string)$attribute->getFrontendInput() !== 'media_image') {
                continue;
            }
            $code = (string)$attribute->getAttributeCode();
            if ($code === '') {
                continue;
            }
            $product->setData($code, 'no_selection');
        }

        $product->setData('media_gallery', ['images' => [], 'values' => []]);
    }

    private function sanitizeUrlKey(string $raw): string
    {
        $base = strtolower(trim($raw));
        $base = preg_replace('/[^a-z0-9]+/i', '-', $base);
        $base = trim((string)$base, '-');

        return $base;
    }

    private function buildUrlKeyCandidates(array $row, array &$data): array
    {
        $rawFromInput = '';
        if (!empty($data['url_key'])) {
            $rawFromInput = (string)$data['url_key'];
            unset($data['url_key']);
        }

        $nameRaw = (string)($data['name'] ?? $data['product_name'] ?? $row['product_name'] ?? '');
        $skuRaw = (string)($row['sku'] ?? $data['sku'] ?? '');

        $candidates = [];

        if ($rawFromInput !== '') {
            $candidates[] = $this->sanitizeUrlKey($rawFromInput);
        }

        $nameKey = $this->sanitizeUrlKey($nameRaw);
        if ($nameKey !== '') {
            $candidates[] = $nameKey;
        }

        $skuKey = $this->sanitizeUrlKey($skuRaw);
        if ($nameKey !== '' && $skuKey !== '') {
            $candidates[] = $nameKey . '-' . $skuKey;
        }

        if ($skuKey !== '') {
            $candidates[] = $skuKey;
        }

        if ($skuKey !== '' && $nameKey !== '') {
            $candidates[] = $skuKey . '-' . $nameKey;
        }

        // Collision fallbacks: keep name simple by default, then try deterministic variants.
        if ($nameKey !== '') {
            for ($i = 1; $i <= 20; $i++) {
                $candidates[] = $nameKey . '-' . $i;
            }
        }
        if ($skuKey !== '') {
            for ($i = 1; $i <= 20; $i++) {
                $candidates[] = $skuKey . '-' . $i;
            }
        }

        $entityId = (int)($row['entity_id'] ?? 0);
        if ($entityId > 0 && $nameKey !== '') {
            $candidates[] = $nameKey . '-' . $entityId;
        }
        if ($entityId > 0 && $skuKey !== '') {
            $candidates[] = $skuKey . '-' . $entityId;
        }

        // Final hard fallback: high-entropy keys to avoid persistent collisions.
        for ($i = 0; $i < 5; $i++) {
            $token = substr(sha1($skuRaw . '|' . $nameRaw . '|' . microtime(true) . '|' . mt_rand()), 0, 10);
            if ($nameKey !== '') {
                $candidates[] = $nameKey . '-' . $token;
            }
            if ($skuKey !== '') {
                $candidates[] = $skuKey . '-' . $token;
            }
        }

        $candidates = array_values(array_unique(array_filter($candidates, function ($value) {
            return trim((string)$value) !== '';
        })));

        if (empty($candidates)) {
            $candidates[] = 'product';
        }

        return $candidates;
    }

    private function isUrlKeyAlreadyExistsException(\Throwable $e): bool
    {
        if ($e instanceof \Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException) {
            return true;
        }

        return stripos((string)$e->getMessage(), 'URL key for specified store already exists') !== false;
    }

    private function doesUrlKeyExistInMagento(string $urlKey): bool
    {
        $key = trim((string)$urlKey);
        if ($key === '') {
            return false;
        }

        $connection = $this->resource->getConnection();
        $rewriteTable = $connection->getTableName('url_rewrite');
        if (!$connection->isTableExists($rewriteTable)) {
            return false;
        }

        $paths = [$key, $key . '.html'];
        $count = (int)$connection->fetchOne(
            $connection->select()
                ->from($rewriteTable, ['cnt' => new \Zend_Db_Expr('COUNT(*)')])
                ->where('request_path IN (?)', $paths)
        );

        return $count > 0;
    }

    private function saveProductWithImageFallback($product): void
    {
        try {
            $this->productRepository->save($product);
        } catch (\Throwable $saveException) {
            if (stripos((string)$saveException->getMessage(), "image doesn't exist") !== false) {
                $this->forceClearAllImageAttributes($product);
                $this->productRepository->save($product);
            } else {
                throw $saveException;
            }
        }
    }

    private function saveProductWithUrlKeyFallback($product, array $urlKeyCandidates): void
    {
        $lastUrlKeyException = null;
        $attemptedKeys = [];

        foreach ($urlKeyCandidates as $candidate) {
            if ($this->doesUrlKeyExistInMagento($candidate)) {
                $attemptedKeys[] = $candidate . ' [exists]';
                continue;
            }

            $product->setUrlKey($candidate);
            $product->setData('url_path', null);
            $attemptedKeys[] = $candidate;
            try {
                $this->saveProductWithImageFallback($product);
                return;
            } catch (\Throwable $saveException) {
                if ($this->isUrlKeyAlreadyExistsException($saveException)) {
                    $lastUrlKeyException = $saveException;
                    continue;
                }

                throw $saveException;
            }
        }

        if ($lastUrlKeyException !== null) {
            throw new \RuntimeException(
                'URL key for specified store already exists. Tried keys: ' . implode(', ', $attemptedKeys),
                0,
                $lastUrlKeyException
            );
        }

        $this->saveProductWithImageFallback($product);
    }

    private function hasAttributeValueInDraftData(array $data, string $attributeCode): bool
    {
        $needle = strtolower(trim($attributeCode));
        foreach ($data as $key => $value) {
            if (strtolower(trim((string)$key)) !== $needle) {
                continue;
            }

            if ($value !== null && trim((string)$value) !== '') {
                return true;
            }

            return false;
        }

        return false;
    }

    private function applyMissingDefaultYesNoAttributes($product, array $data): void
    {
        if (!$product) {
            return;
        }

        $defaultYesAttributes = [
            'webpos_visible' => 1,
            'coupon_apply' => 1,
            'vinculum_mapped' => 0,
            'vinculum_synced' => 0,
        ];

        foreach ($defaultYesAttributes as $code => $defaultValue) {
            if ($this->hasAttributeValueInDraftData($data, $code)) {
                continue;
            }

            try {
                $attribute = $product->getResource()->getAttribute($code);
            } catch (\Throwable $e) {
                $attribute = null;
            }

            if (!$attribute || !(int)$attribute->getId()) {
                continue;
            }

            $current = $product->getData($code);
            if ($current === null || trim((string)$current) === '') {
                $product->setData($code, $defaultValue);
            }
        }
    }

    private function isDue(string $nextRunAt): bool
    {
        if (trim($nextRunAt) === '') {
            return true;
        }

        try {
            $next = new \DateTimeImmutable($nextRunAt);
            $now = new \DateTimeImmutable('now');

            return $now >= $next;
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function calculateNextRunAtRecurring(): string
    {
        $startRaw = $this->getStartTime();
        $interval = $this->getInterval();
        $intervalValue = $this->getIntervalValue();

        $start = new \DateTimeImmutable($startRaw !== '' ? $startRaw : 'now');
        $now = new \DateTimeImmutable('now');
        $next = $start;
        $safety = 0;

        while ($next <= $now && $safety < 100000) {
            if ($interval === 'hourly') {
                $next = $next->modify('+1 hour');
            } elseif ($interval === 'daily') {
                $next = $next->modify('+1 day');
            } else {
                $step = $intervalValue > 0 ? $intervalValue : 1;
                $next = $next->modify('+' . $step . ' minutes');
            }
            $safety++;
        }

        return $next->format('Y-m-d H:i:s');
    }

    private function countQueuedRows(): int
    {
        $connection = $this->resource->getConnection();
        $draftTable = $connection->getTableName('artm_massproducts_draft');

        return (int)$connection->fetchOne(
            $connection->select()
                ->from($draftTable, ['cnt' => new \Zend_Db_Expr('COUNT(*)')])
                ->where('status = ?', 'queued')
        );
    }

    private function disableCronProcessing(): void
    {
        $this->configWriter->save(self::XML_PATH_ENABLE, 0);
    }

    private function saveNextRunAt(string $nextRunAt): void
    {
        $this->configWriter->save(self::XML_PATH_NEXT_RUN_AT, $nextRunAt);
    }

    public function execute()
    {
        if (!$this->isCronEnabled()) {
            return;
        }

        if (!$this->isDue($this->getNextRunAt())) {
            return;
        }

        $cronLogId = $this->uploadLogger->startCronRun('cron', 'Cron execution started.');
        $successCount = 0;
        $failureCount = 0;
        $totalProducts = 0;

        $connection = $this->resource->getConnection();
        $draftTable = $connection->getTableName('artm_massproducts_draft');
        $batchSize = max(1, $this->getBatchSize());

        // Get queued products in configured batch size.
        $queuedRows = $connection->fetchAll(
            $connection->select()
                ->from($draftTable)
                ->where('status = ?', 'queued')
                ->order('entity_id ASC')
                ->limit($batchSize)
        );
            $totalProducts = count($queuedRows);

        foreach ($queuedRows as $row) {
            $id = (int)$row['entity_id'];
            $data = [];
            if (!empty($row['mapped_data'])) {
                $decoded = json_decode((string)$row['mapped_data'], true);
                if (is_array($decoded)) {
                    $data = $decoded;
                }
            }
            $data = $this->resolveDraftAttributeData($id, $data);
            $categoryIds = $this->extractCategoryIds($data);
            $msiQtyMap = $this->extractMsiQuantities($data);
            $urlKeyCandidates = $this->buildUrlKeyCandidates($row, $data);
            $resolvedProductName = trim((string)($data['name'] ?? $data['product_name'] ?? $row['product_name'] ?? (string)($row['sku'] ?? '')));
            if ($resolvedProductName === '') {
                $resolvedProductName = (string)($row['sku'] ?? '');
            }

            // Update to processing
            $processingUpdate = $this->prepareDraftUpdate([
                'status' => 'processing',
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $connection->update($draftTable, $processingUpdate, ['entity_id = ?' => $id]);

            try {
                // Check if product exists
                try {
                    $this->productRepository->get($row['sku']);
                    throw new \Exception('Product with this SKU already exists');
                } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                    // Proceed
                }

                $product = $this->productFactory->create();
                $product->setSku($row['sku']);
                $product->setName($resolvedProductName);
                $product->setAttributeSetId($row['attribute_set_id']);
                $product->setTypeId('simple');
                $product->setVisibility(4);
                $product->setStatus(1);
                $product->setPrice($data['price'] ?? 0);
                $product->setWebsiteIds([1]);
                $product->setUrlKey($urlKeyCandidates[0]);
                if (!empty($categoryIds)) {
                    $product->setCategoryIds($categoryIds);
                }

                foreach ($data as $code => $value) {
                    $normalizedCode = strtolower(trim((string)$code));
                    if (in_array($normalizedCode, [
                        'sku',
                        'name',
                        'product_name',
                        'attribute_set_id',
                        'type_id',
                        'price',
                        'image',
                        'small_image',
                        'thumbnail',
                        'swatch_image',
                        'swatch_thumbnail',
                        'base_image',
                        'image_label',
                        'small_image_label',
                        'thumbnail_label',
                        'media_gallery'
                    ], true)) {
                        continue;
                    }

                    $attribute = null;
                    try {
                        $attribute = $product->getResource()->getAttribute((string)$code)
                            ?: $product->getResource()->getAttribute($normalizedCode);
                    } catch (\Throwable $e) {
                        $attribute = null;
                    }
                    if ($attribute && (string)$attribute->getFrontendInput() === 'media_image') {
                        continue;
                    }

                    $product->setData($code, $value);
                }

                $this->applyMissingDefaultYesNoAttributes($product, $data);

                $this->sanitizeImageAttributesBeforeSave($product);

                $this->saveProductWithUrlKeyFallback($product, $urlKeyCandidates);

                try {
                    $productForMedia = $this->productRepository->get((string)$row['sku'], false, null, true);
                    $this->applyDraftMediaToProduct($productForMedia, $id);
                    $this->sanitizeImageAttributesBeforeSave($productForMedia);
                    $this->productRepository->save($productForMedia);
                } catch (\Throwable $mediaException) {
                    // Keep product creation successful even if media attachment has issues.
                }
                $this->saveMsiSourceItems((string)$row['sku'], $msiQtyMap);
                $this->updateLegacyStock((string)$row['sku'], $msiQtyMap);

                // Success
                $successUpdate = $this->prepareDraftUpdate([
                    'status' => 'success',
                    'magento_product_id' => $product->getId(),
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);
                $connection->update($draftTable, $successUpdate, ['entity_id = ?' => $id]);
                $successCount++;
                $this->uploadLogger->logProductUpload(
                    (int)$product->getId(),
                    (string)($row['sku'] ?? ''),
                    $id,
                    'cron',
                    'success',
                    'Product processed successfully via cron.'
                );
                $this->uploadLogger->updateDraftLastUpload($id, 'cron');

            } catch (\Throwable $e) {
                // Failed
                $failedUpdate = $this->prepareDraftUpdate([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);
                $connection->update($draftTable, $failedUpdate, ['entity_id = ?' => $id]);
                $failureCount++;
                $this->uploadLogger->logProductUpload(
                    0,
                    (string)($row['sku'] ?? ''),
                    $id,
                    'cron',
                    'failed',
                    $e->getMessage()
                );
            }
        }

        $mode = $this->getMode();
        $stopAfterComplete = $this->shouldStopAfterComplete();
        $remainingQueued = $this->countQueuedRows();

        if ($mode === 'run_once') {
            $this->uploadLogger->finishCronRun(
                $cronLogId,
                $totalProducts,
                $successCount,
                $failureCount,
                sprintf('Run once completed. Success: %d, Failed: %d', $successCount, $failureCount)
            );
            $this->disableCronProcessing();
            return;
        }

        if ($stopAfterComplete && $remainingQueued <= 0) {
            $this->uploadLogger->finishCronRun(
                $cronLogId,
                $totalProducts,
                $successCount,
                $failureCount,
                sprintf('Recurring run completed and auto-stopped. Success: %d, Failed: %d', $successCount, $failureCount)
            );
            $this->disableCronProcessing();
            return;
        }

        $this->saveNextRunAt($this->calculateNextRunAtRecurring());
        $this->uploadLogger->finishCronRun(
            $cronLogId,
            $totalProducts,
            $successCount,
            $failureCount,
            sprintf('Recurring run completed. Success: %d, Failed: %d', $successCount, $failureCount)
        );
    }
}
