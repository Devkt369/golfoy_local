<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Ready;

use ArtM\MassProducts\Model\Logger\ActivityLogger;
use ArtM\MassProducts\Model\Logger\UploadLogger;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Filesystem\DirectoryList;

class AddProduct extends Action
{
    private $resultJsonFactory;
    private $productFactory;
        private const LOG_TIMEZONE = 'Asia/Kolkata';
    private $productRepository;
    private $resource;
    private $uploadLogger;
    private $activityLogger;
    private $draftColumns = null;
    private $currentAdminUsername = 'manual';
    private $currentAdminUserId = 0;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resource,
        UploadLogger $uploadLogger,
        ActivityLogger $activityLogger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
        $this->resource = $resource;
        $this->uploadLogger = $uploadLogger;
        $this->activityLogger = $activityLogger;
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

    private function collectIds(): array
    {
        $ids = (array)$this->getRequest()->getParam('ids', []);
        if (empty($ids)) {
            $single = (int)$this->getRequest()->getParam('id', 0);
            if ($single > 0) {
                $ids = [$single];
            }
        }

        return array_values(array_filter(array_map('intval', $ids), function ($id) {
            return $id > 0;
        }));
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

        $ids = array_values(array_unique(array_filter(array_map('intval', array_map('trim', explode(',', $raw))), function ($id) {
            return $id > 0;
        })));

        return $ids;
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

    private function saveProductWithImageFallback($product, string &$mediaWarning): void
    {
        try {
            $this->productRepository->save($product);
        } catch (\Throwable $saveException) {
            if (stripos((string)$saveException->getMessage(), "image doesn't exist") !== false) {
                $this->forceClearAllImageAttributes($product);
                $this->productRepository->save($product);
                $mediaWarning = ($mediaWarning !== '' ? $mediaWarning . ' | ' : '')
                    . 'Primary save required image fallback: ' . $saveException->getMessage();
            } else {
                throw $saveException;
            }
        }

        try {
            $this->_eventManager->dispatch(
                'controller_action_catalog_product_save_entity_after',
                ['controller' => $this, 'product' => $product]
            );
        } catch (\Throwable $eventException) {
            // Product is already saved; some core/3rd-party observers on this event
            // assume the real admin Save controller's request shape and can fail here.
            // Do not let a listener failure abort an already-successful save.
        }
    }

    private function saveProductWithUrlKeyFallback($product, array $urlKeyCandidates, string &$mediaWarning): void
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
                $this->saveProductWithImageFallback($product, $mediaWarning);
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

        $this->saveProductWithImageFallback($product, $mediaWarning);
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

    private function parseSkuList($raw): array
    {
        $seen = [];
        $list = [];
        foreach (preg_split('/[\n,]+/', (string)$raw) as $token) {
            $sku = trim((string)$token);
            if ($sku === '' || isset($seen[$sku])) {
                continue;
            }
            $seen[$sku] = true;
            $list[] = $sku;
        }
        return $list;
    }

    private function normalizeVariationAttributes($raw): array
    {
        $items = is_array($raw) ? $raw : preg_split('/[\n,]+/', (string)$raw);
        $normalized = [];

        foreach ((array)$items as $item) {
            $code = trim((string)$item);
            if ($code === '' || in_array($code, $normalized, true)) {
                continue;
            }
            $normalized[] = $code;
        }

        return $normalized;
    }

    private function resolveVariationAttributesFromRow(array $row): array
    {
        $codes = $this->normalizeVariationAttributes($row['variation_attributes'] ?? []);

        if (empty($codes) && !empty($row['raw_data'])) {
            $raw = json_decode((string)$row['raw_data'], true);
            if (is_array($raw)) {
                $codes = $this->normalizeVariationAttributes($raw['variation_attributes'] ?? []);
            }
        }

        return $codes;
    }

    private function resolveRelationFromRow(array $row): array
    {
        $parentSku = trim((string)($row['parent_sku'] ?? ''));
        $childSkus = $this->parseSkuList((string)($row['child_sku'] ?? ''));

        if (($parentSku === '' || empty($childSkus)) && !empty($row['raw_data'])) {
            $raw = json_decode((string)$row['raw_data'], true);
            if (is_array($raw)) {
                if ($parentSku === '' && isset($raw['parent'])) {
                    $parentSku = trim((string)$raw['parent']);
                }
                if (empty($childSkus) && isset($raw['child'])) {
                    $childSkus = $this->parseSkuList((string)$raw['child']);
                }
            }
        }

        return [
            'parent' => $parentSku,
            'children' => $childSkus,
        ];
    }

    private function buildRelationMaps(array $rows): array
    {
        $parentBySku = [];
        $childrenBySku = [];

        foreach ($rows as $row) {
            $sku = trim((string)($row['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }

            $relation = $this->resolveRelationFromRow($row);
            $parentSku = $relation['parent'];
            $childSkus = $relation['children'];

            if ($parentSku !== '') {
                $parentBySku[$sku] = $parentSku;
                if (!isset($childrenBySku[$parentSku])) {
                    $childrenBySku[$parentSku] = [];
                }
                if (!in_array($sku, $childrenBySku[$parentSku], true)) {
                    $childrenBySku[$parentSku][] = $sku;
                }
            }

            foreach ($childSkus as $childSku) {
                if (!isset($childrenBySku[$sku])) {
                    $childrenBySku[$sku] = [];
                }
                if (!in_array($childSku, $childrenBySku[$sku], true)) {
                    $childrenBySku[$sku][] = $childSku;
                }
                if (!isset($parentBySku[$childSku])) {
                    $parentBySku[$childSku] = $sku;
                }
            }
        }

        return [
            'parent_by_sku' => $parentBySku,
            'children_by_sku' => $childrenBySku,
        ];
    }

    private function linkConfigurableChildren(string $parentSku, array $childSkus, array $parentRow = []): void
    {
        $parentSku = trim($parentSku);
        if ($parentSku === '' || empty($childSkus)) {
            return;
        }

        if (!class_exists('Magento\\ConfigurableProduct\\Model\\ResourceModel\\Product\\Type\\Configurable')) {
            throw new \RuntimeException('Magento ConfigurableProduct module is not available.');
        }

        $parent = $this->productRepository->get($parentSku, false, null, true);
        $selectedVariationCodes = $this->resolveVariationAttributesFromRow($parentRow);
        $childProductsBySku = [];
        foreach ($childSkus as $childSku) {
            $cleanChildSku = trim((string)$childSku);
            if ($cleanChildSku === '') {
                continue;
            }
            try {
                $childProductsBySku[$cleanChildSku] = $this->productRepository->get($cleanChildSku, false, null, true);
            } catch (\Throwable $e) {
                // Skip missing child here; existing downstream logic already reports skipped children.
            }
        }

        if (!empty($selectedVariationCodes)) {
            $validVariationAttrIds = [];
            $invalidVariationCodes = [];
            $resource = $parent->getResource();

            foreach ($selectedVariationCodes as $code) {
                $attr = null;
                try {
                    $attr = $resource->getAttribute($code);
                } catch (\Throwable $e) {
                    $attr = null;
                }

                if (!$attr || !(int)$attr->getId()) {
                    $invalidVariationCodes[] = $code;
                    continue;
                }

                $frontendInput = strtolower(trim((string)$attr->getFrontendInput()));
                if ($frontendInput !== 'select') {
                    $invalidVariationCodes[] = $code;
                    continue;
                }

                if ((int)$attr->getIsGlobal() !== 1) {
                    $invalidVariationCodes[] = $code;
                    continue;
                }

                $attrIsConfigurable = $attr->getData('is_configurable');
                if ($attrIsConfigurable !== null && (int)$attrIsConfigurable !== 1) {
                    $invalidVariationCodes[] = $code;
                    continue;
                }

                $validVariationAttrIds[] = (int)$attr->getId();
            }

            $validVariationAttrIds = array_values(array_unique(array_filter($validVariationAttrIds)));

            if (empty($validVariationAttrIds)) {
                foreach ((array)$parent->getAttributes() as $parentAttr) {
                    $attrId = (int)$parentAttr->getId();
                    $attrCode = trim((string)$parentAttr->getAttributeCode());
                    $frontendInput = strtolower(trim((string)$parentAttr->getFrontendInput()));

                    if ($attrId <= 0 || $attrCode === '') {
                        continue;
                    }
                    if ($frontendInput !== 'select') {
                        continue;
                    }
                    if ((int)$parentAttr->getIsGlobal() !== 1) {
                        continue;
                    }
                    $parentAttrIsConfigurable = $parentAttr->getData('is_configurable');
                    if ($parentAttrIsConfigurable !== null && (int)$parentAttrIsConfigurable !== 1) {
                        continue;
                    }

                    $allChildrenHaveValue = true;
                    foreach ($childProductsBySku as $childProduct) {
                        $childVal = $childProduct->getData($attrCode);
                        if ($childVal === null || trim((string)$childVal) === '') {
                            $allChildrenHaveValue = false;
                            break;
                        }
                    }

                    if ($allChildrenHaveValue && !empty($childProductsBySku)) {
                        $validVariationAttrIds[] = $attrId;
                    }
                }

                $validVariationAttrIds = array_values(array_unique(array_filter($validVariationAttrIds)));
            }

            if (!empty($validVariationAttrIds)) {
                $existingUsedIds = [];
                $typeInstance = $parent->getTypeInstance();
                if ($typeInstance && method_exists($typeInstance, 'getUsedProductAttributeIds')) {
                    $existingUsedIds = array_map('intval', (array)$typeInstance->getUsedProductAttributeIds($parent));
                }

                $usedIds = array_values(array_unique(array_filter(array_merge($existingUsedIds, $validVariationAttrIds))));
                if (!empty($usedIds) && $typeInstance && method_exists($typeInstance, 'setUsedProductAttributeIds')) {
                    $typeInstance->setUsedProductAttributeIds($usedIds, $parent);
                    $parent->setCanSaveConfigurableAttributes(true);
                    $parent->setConfigurableAttributesData($typeInstance->getConfigurableAttributesAsArray($parent));
                    try {
                        $this->productRepository->save($parent);
                        $parent = $this->productRepository->get($parentSku, false, null, true);
                    } catch (\Throwable $e) {
                        throw new \RuntimeException(
                            'Unable to apply selected configurable attributes on parent ' . $parentSku
                            . '. Selected: ' . implode(', ', $selectedVariationCodes)
                            . '. ' . $e->getMessage()
                        );
                    }
                }
            }

            if (!empty($invalidVariationCodes) && empty($validVariationAttrIds)) {
                throw new \RuntimeException(
                    'Selected variation attributes are not configurable-capable for parent ' . $parentSku . ': '
                    . implode(', ', $invalidVariationCodes)
                    . '. Please select dropdown/swatch attributes like size/color.'
                );
            }
        }

        $configurableAttributeCodes = [];
        $typeInstance = $parent->getTypeInstance();
        if ($typeInstance && method_exists($typeInstance, 'getConfigurableAttributesAsArray')) {
            $attrRows = (array)$typeInstance->getConfigurableAttributesAsArray($parent);
            foreach ($attrRows as $attrRow) {
                $code = trim((string)($attrRow['attribute_code'] ?? ''));
                if ($code !== '') {
                    $configurableAttributeCodes[$code] = true;
                }
            }
        }
        $configurableAttributeCodes = array_keys($configurableAttributeCodes);

        if (empty($configurableAttributeCodes)) {
            $selectedSuffix = !empty($selectedVariationCodes)
                ? (' Selected variation attributes: ' . implode(', ', $selectedVariationCodes) . '.')
                : '';
            throw new \RuntimeException(
                'Parent ' . $parentSku . ' has no configurable attributes defined. '
                . 'Please configure configurable attributes on parent first (e.g. size/color).'
                . $selectedSuffix
            );
        }

        $childIds = [];
        $skippedChildren = [];

        foreach ($childSkus as $childSku) {
            $sku = trim((string)$childSku);
            if ($sku === '') {
                continue;
            }
            try {
                $child = $this->productRepository->get($sku, false, null, true);
                $childId = (int)$child->getId();
                if ($childId > 0) {
                    if ((string)$child->getTypeId() !== 'simple') {
                        $skippedChildren[] = $sku . ' (not simple type)';
                        continue;
                    }

                    $missingCodes = [];
                    foreach ($configurableAttributeCodes as $code) {
                        $val = $child->getData($code);
                        if ($val === null || trim((string)$val) === '' || trim((string)$val) === '0') {
                            $missingCodes[] = $code;
                        }
                    }
                    if (!empty($missingCodes)) {
                        $skippedChildren[] = $sku . ' (missing: ' . implode(', ', $missingCodes) . ')';
                        continue;
                    }

                    $childIds[] = $childId;
                }
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Skip missing child SKU and continue with available children.
                $skippedChildren[] = $sku . ' (not found in Magento)';
                continue;
            }
        }
        $childIds = array_values(array_unique(array_filter($childIds)));
        if (empty($childIds)) {
            $suffix = !empty($skippedChildren) ? (' Skipped: ' . implode('; ', $skippedChildren) . '.') : '';
            throw new \RuntimeException('No valid child products available in Magento to associate with configurable ' . $parentSku . '.' . $suffix);
        }

        $configurableResource = ObjectManager::getInstance()->get('Magento\\ConfigurableProduct\\Model\\ResourceModel\\Product\\Type\\Configurable');

        // Preserve already-associated children while adding new valid children.
        $existingChildIds = [];
        if ($typeInstance && method_exists($typeInstance, 'getUsedProducts')) {
            foreach ((array)$typeInstance->getUsedProducts($parent) as $existingChild) {
                $existingId = (int)($existingChild ? $existingChild->getId() : 0);
                if ($existingId > 0) {
                    $existingChildIds[] = $existingId;
                }
            }
        }

        $finalChildIds = array_values(array_unique(array_merge($existingChildIds, $childIds)));
        $configurableResource->saveProducts($parent, $finalChildIds);
    }

    private function createProductFromRow(array $row, string $forcedTypeId = 'simple'): array
    {
        $connection = $this->resource->getConnection();
        $draftTable = $connection->getTableName('artm_massproducts_draft');
        $id = (int)($row['entity_id'] ?? 0);
        $mediaWarning = '';
        $sku = (string)($row['sku'] ?? '');

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
        $resolvedProductName = trim((string)($data['name'] ?? $data['product_name'] ?? $row['product_name'] ?? $sku));
        if ($resolvedProductName === '') {
            $resolvedProductName = $sku;
        }
        $msiReport = [
            'source_qty_map' => $msiQtyMap,
            'source_count' => count($msiQtyMap),
            'total_qty' => array_sum(array_map('floatval', $msiQtyMap)),
            'inventory_api_available' => interface_exists('Magento\\InventoryApi\\Api\\SourceItemsSaveInterface')
                && class_exists('Magento\\InventoryApi\\Api\\Data\\SourceItemInterfaceFactory'),
            'legacy_stock_api_available' => interface_exists('Magento\\CatalogInventory\\Api\\StockRegistryInterface'),
            'source_items_save_attempted' => false,
            'legacy_stock_save_attempted' => false,
        ];

        $product = null;
        $failureMessage = '';
        try {
            try {
                $existingProduct = $this->productRepository->get($sku, false, null, true);

                // Reuse existing Magento SKU (do not fail) so configurable linking can proceed.
                $existingProductId = (int)$existingProduct->getId();

                $successUpdate = $this->prepareDraftUpdate([
                    'status' => 'success',
                    'magento_product_id' => $existingProductId > 0 ? $existingProductId : null,
                    'added_by' => $this->currentAdminUsername,
                    'error_message' => null,
                    'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                ]);
                $connection->update($draftTable, $successUpdate, ['entity_id = ?' => $id]);

                return [
                    'success' => true,
                    'id' => $id,
                    'product_id' => $existingProductId,
                    'message' => 'Product already exists in Magento. Reusing existing SKU.',
                    'warning' => '',
                    'msi_report' => [
                        'source_qty_map' => [],
                        'source_count' => 0,
                        'total_qty' => 0,
                        'inventory_api_available' => interface_exists('Magento\\InventoryApi\\Api\\SourceItemsSaveInterface')
                            && class_exists('Magento\\InventoryApi\\Api\\Data\\SourceItemInterfaceFactory'),
                        'legacy_stock_api_available' => interface_exists('Magento\\CatalogInventory\\Api\\StockRegistryInterface'),
                        'source_items_save_attempted' => false,
                        'legacy_stock_save_attempted' => false,
                    ],
                    'media_report' => [
                        'draft_id' => $id,
                        'total_rows' => 0,
                        'attached' => 0,
                        'skipped_missing' => 0,
                        'skipped_extension' => 0,
                        'errors' => [],
                    ],
                ];
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // SKU does not exist, continue.
            }

            $product = $this->productFactory->create();
            $product->setSku($sku);
            $product->setName($resolvedProductName);
            $product->setAttributeSetId((int)$row['attribute_set_id']);
            $product->setTypeId($forcedTypeId === 'configurable' ? 'configurable' : 'simple');
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
                    'parent',
                    'child',
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

            $this->saveProductWithUrlKeyFallback($product, $urlKeyCandidates, $mediaWarning);

            $savedProductId = (int)$product->getId();
            $savedProductUrl = (string)$product->getUrlKey();
            if ($savedProductId <= 0) {
                try {
                    $persistedProduct = $this->productRepository->get($sku, false, null, true);
                    $savedProductId = (int)$persistedProduct->getId();
                    if ($savedProductId > 0) {
                        $product->setId($savedProductId);
                    }
                } catch (\Throwable $idResolveException) {
                    $savedProductId = 0;
                }
            }

            $mediaReport = [
                'draft_id' => $id,
                'total_rows' => 0,
                'attached' => 0,
                'skipped_missing' => 0,
                'skipped_extension' => 0,
                'errors' => [],
            ];
            try {
                $productForMedia = $this->productRepository->get((string)$row['sku'], false, null, true);
                $mediaReport = $this->applyDraftMediaToProduct($productForMedia, $id);
                $this->sanitizeImageAttributesBeforeSave($productForMedia);
                $this->productRepository->save($productForMedia);
                $this->_eventManager->dispatch(
                    'controller_action_catalog_product_save_entity_after',
                    ['controller' => $this, 'product' => $productForMedia]
                );
            } catch (\Throwable $mediaException) {
                $mediaWarning = ($mediaWarning !== '' ? $mediaWarning . ' | ' : '') . 'Media attachment warning: ' . $mediaException->getMessage();
            }

            if ((int)($mediaReport['total_rows'] ?? 0) > 0 && (int)($mediaReport['attached'] ?? 0) === 0) {
                $mediaWarning = ($mediaWarning !== '' ? $mediaWarning . ' | ' : '')
                    . 'No images attached. Check media_report for details.';
            }

            $this->saveMsiSourceItems($sku, $msiQtyMap);
            $msiReport['source_items_save_attempted'] = !empty($msiQtyMap);
            $this->updateLegacyStock($sku, $msiQtyMap, $forcedTypeId);
            $msiReport['legacy_stock_save_attempted'] = !empty($msiQtyMap);

            $successUpdate = $this->prepareDraftUpdate([
                'status' => 'success',
                'magento_product_id' => $savedProductId > 0 ? $savedProductId : null,
                'added_by' => $this->currentAdminUsername,
                'error_message' => null,
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                'product_url' => $savedProductUrl,
            ]);
            $connection->update($draftTable, $successUpdate, ['entity_id = ?' => $id]);

            return [
                'success' => true,
                'id' => $id,
                'product_id' => $savedProductId,
                'message' => $mediaWarning !== '' ? ('Product created with warning: ' . $mediaWarning) : 'Product created',
                'warning' => $mediaWarning,
                'msi_report' => $msiReport,
                'media_report' => $mediaReport,
            ];
        } catch (\Throwable $e) {
            $failureMessage = $e->getMessage();
            $failedUpdate = $this->prepareDraftUpdate([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $connection->update($draftTable, $failedUpdate, ['entity_id = ?' => $id]);

            return [
                'success' => false,
                'id' => $id,
                'message' => $e->getMessage(),
                'debug' => [
                    'exception_class' => get_class($e),
                    'trace_top' => array_slice(explode("\n", $e->getTraceAsString()), 0, 8),
                ],
            ];
        } finally {
            if ($product && (int)$product->getId() > 0) {
                $this->uploadLogger->logProductUpload(
                    (int)$product->getId(),
                    $sku,
                    $id,
                    'manual',
                    'success',
                    $mediaWarning
                );
                $this->uploadLogger->updateDraftLastUpload($id, 'manual');
            }

            if (!$product || !(int)$product->getId()) {
                $this->uploadLogger->logProductUpload(
                    0,
                    $sku,
                    $id,
                    'manual',
                    'failed',
                    $failureMessage !== '' ? $failureMessage : 'Product upload failed.'
                );
            }
        }
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $user = $this->_auth->getUser();
        $this->currentAdminUsername = $user ? (string)$user->getUserName() : 'manual';
        $this->currentAdminUserId = $user ? (int)$user->getId() : 0;

        $ids = $this->collectIds();
        if (empty($ids)) {
            return $result->setData(['success' => false, 'message' => 'Missing ID(s)']);
        }

        $connection = $this->resource->getConnection();
        $draftTable = $connection->getTableName('artm_massproducts_draft');

        $rows = $connection->fetchAll(
            $connection->select()
                ->from($draftTable)
                ->where('entity_id IN (?)', $ids)
                ->where('status IN (?)', ['ready', 'failed'])
        );

        if (empty($rows)) {
            return $result->setData([
                'success' => false,
                'message' => 'No ready/failed rows found for product creation.'
            ]);
        }

        // Auto-include related parent/child rows so configurable association can be completed
        // even when only one side was selected from the grid.
        $relatedSkus = [];
        foreach ($rows as $row) {
            $sku = trim((string)($row['sku'] ?? ''));
            if ($sku !== '') {
                $relatedSkus[$sku] = true;
            }

            $relation = $this->resolveRelationFromRow($row);
            $parentSku = trim((string)$relation['parent']);
            if ($parentSku !== '') {
                $relatedSkus[$parentSku] = true;
            }
            foreach ((array)$relation['children'] as $childSku) {
                $child = trim((string)$childSku);
                if ($child !== '') {
                    $relatedSkus[$child] = true;
                }
            }
        }

        if (!empty($relatedSkus)) {
            $relatedRows = $connection->fetchAll(
                $connection->select()
                    ->from($draftTable)
                    ->where('sku IN (?)', array_keys($relatedSkus))
                    ->where('status IN (?)', ['ready', 'failed'])
            );

            $rowsById = [];
            foreach ($rows as $row) {
                $rowsById[(int)($row['entity_id'] ?? 0)] = $row;
            }
            foreach ($relatedRows as $row) {
                $rowsById[(int)($row['entity_id'] ?? 0)] = $row;
            }
            $rows = array_values($rowsById);
        }

        $processed = [];
        $successCount = 0;
        $failedCount = 0;
        $successfulBySku = [];

        $relationMaps = $this->buildRelationMaps($rows);
        $childrenBySku = $relationMaps['children_by_sku'];

        usort($rows, function (array $a, array $b) use ($childrenBySku) {
            $skuA = trim((string)($a['sku'] ?? ''));
            $skuB = trim((string)($b['sku'] ?? ''));
            $aIsConfigurable = $skuA !== '' && !empty($childrenBySku[$skuA]);
            $bIsConfigurable = $skuB !== '' && !empty($childrenBySku[$skuB]);
            if ($aIsConfigurable === $bIsConfigurable) {
                return (int)($a['entity_id'] ?? 0) <=> (int)($b['entity_id'] ?? 0);
            }
            return $aIsConfigurable ? 1 : -1;
        });

        foreach ($rows as $row) {
            $sku = trim((string)($row['sku'] ?? ''));
            $isConfigurable = $sku !== '' && !empty($childrenBySku[$sku]);
            $forcedTypeId = $isConfigurable ? 'configurable' : 'simple';

            $item = $this->createProductFromRow($row, $forcedTypeId);
            $processed[] = $item;
            if (!empty($item['success'])) {
                $successCount++;
                if ($sku !== '') {
                    $successfulBySku[$sku] = $item;
                }

                $dt = new \DateTime();
                $this->activityLogger->log([
                    'action_type' => 'product_added',
                    'sku' => $sku,
                    'draft_id' => (int)($row['entity_id'] ?? 0),
                    'admin_user_id' => $this->currentAdminUserId,
                    'admin_username' => $this->currentAdminUsername,
                    'details' => $sku . ' Product added from ready by ' . $this->currentAdminUsername,
                    'comment' => 'Product added from ready tab.',
                    'occurred_at' => $dt->format('Y-m-d H:i:s'),
                ]);
            } else {
                $failedCount++;
            }
        }

        // Link configurable parents with their child products after all products are created.
        // Important: this also supports parent SKUs that already exist in Magento and are not
        // part of the current draft selection.
        $rowBySku = [];
        foreach ($rows as $row) {
            $sku = trim((string)($row['sku'] ?? ''));
            if ($sku !== '') {
                $rowBySku[$sku] = $row;
            }
        }

        foreach ($childrenBySku as $parentSku => $parentChildren) {
            $parentSku = trim((string)$parentSku);
            if ($parentSku === '') {
                continue;
            }

            $childSkus = array_values(array_unique(array_filter(array_map('trim', (array)$parentChildren))));
            if (empty($childSkus)) {
                continue;
            }

            try {
                // Associate all declared children, including SKUs already existing in Magento.
                $this->linkConfigurableChildren($parentSku, $childSkus, $rowBySku[$parentSku] ?? []);
            } catch (\Throwable $e) {
                $failedCount++;
                if (isset($successfulBySku[$parentSku])) {
                    $successCount = max(0, $successCount - 1);
                }

                if (isset($rowBySku[$parentSku])) {
                    $parentRow = $rowBySku[$parentSku];
                    $connection->update(
                        $draftTable,
                        $this->prepareDraftUpdate([
                            'status' => 'failed',
                            'error_message' => 'Configurable association failed: ' . $e->getMessage(),
                            'updated_at' => (new \DateTime())->format('Y-m-d H:i:s'),
                        ]),
                        ['entity_id = ?' => (int)$parentRow['entity_id']]
                    );

                    $processed[] = [
                        'success' => false,
                        'id' => (int)$parentRow['entity_id'],
                        'message' => 'Configurable association failed for ' . $parentSku . ': ' . $e->getMessage(),
                    ];
                } else {
                    $processed[] = [
                        'success' => false,
                        'id' => 0,
                        'message' => 'Configurable association failed for existing parent ' . $parentSku . ': ' . $e->getMessage(),
                    ];
                }
            }
        }

        if (count($ids) === 1) {
            $single = $processed[0];
            return $result->setData([
                'success' => (bool)$single['success'],
                'message' => (string)$single['message'],
                'product_id' => (int)($single['product_id'] ?? 0),
                'processed' => $processed,
                'success_count' => $successCount,
                'failed_count' => $failedCount,
            ]);
        }

        return $result->setData([
            'success' => $successCount > 0,
            'message' => sprintf('Mass add complete. Success: %d, Failed: %d.', $successCount, $failedCount),
            'processed' => $processed,
            'success_count' => $successCount,
            'failed_count' => $failedCount,
        ]);
    }
}
