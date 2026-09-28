<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Success;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Psr\Log\LoggerInterface;

class AddImagesToProduct extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    private ProductRepositoryInterface $productRepository;
    private ResourceConnection $resource;
    private LoggerInterface $logger;

    public function __construct(
        Context $context,
        ProductRepositoryInterface $productRepository,
        ResourceConnection $resource,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
        $this->resource = $resource;
        $this->logger = $logger;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $debugMode = (int)$this->getRequest()->getParam('debug', 0) === 1;
        $debugRows = [];

        try {
            $draftId = (int)$this->getRequest()->getParam('draft_id', 0);
            $sku = trim((string)$this->getRequest()->getParam('sku', ''));
            $productId = (int)$this->getRequest()->getParam('product_id', 0);

            if ($draftId <= 0) {
                return $result->setData(['success' => false, 'message' => 'Invalid draft id.']);
            }

            $connection = $this->resource->getConnection();

            if ($sku === '') {
                $draftTable = $connection->getTableName('artm_massproducts_draft');
                if ($connection->isTableExists($draftTable)) {
                    $sku = trim((string)$connection->fetchOne(
                        $connection->select()->from($draftTable, ['sku'])->where('entity_id = ?', $draftId)
                    ));
                }
            }

            if ($sku === '' && $productId <= 0) {
                return $result->setData(['success' => false, 'message' => 'Missing SKU/product id for this row.']);
            }

            if ($sku !== '') {
                $product = $this->productRepository->get($sku, true, null, true);
            } else {
                $product = $this->productRepository->getById($productId, true, null, true);
                $sku = (string)$product->getSku();
            }

            if ($sku === '') {
                return $result->setData(['success' => false, 'message' => 'Unable to resolve SKU for product.']);
            }

            $productId = (int)$product->getId();

            $mediaTable = $connection->getTableName('artm_massproducts_draft_media');
            if (!$connection->isTableExists($mediaTable)) {
                return $result->setData(['success' => false, 'message' => 'Temporary media table not found.']);
            }

            $rows = $connection->fetchAll(
                $connection->select()
                    ->from($mediaTable, ['media_id', 'file_path', 'roles', 'position'])
                    ->where('draft_id = ?', $draftId)
                    ->order('position ASC')
                    ->order('media_id ASC')
            );

            if (empty($rows)) {
                return $result->setData(['success' => false, 'message' => 'No temporary images found for this product.']);
            }

            $attached = 0;
            $skipped = 0;
            $errors = [];
            $requestedPositions = [];
            $requestedPositionsByHash = [];

            foreach ($rows as $row) {
                $relPath = ltrim((string)($row['file_path'] ?? ''), '/');
                if ($relPath === '') {
                    $skipped++;
                    continue;
                }

                $sourceAbsPath = BP . '/pub/media/' . $relPath;
                if (!file_exists($sourceAbsPath)) {
                    $errors[] = ['file_path' => $relPath, 'message' => 'Source file not found.'];
                    $skipped++;
                    continue;
                }

                if (!is_readable($sourceAbsPath)) {
                    $errors[] = ['file_path' => $relPath, 'message' => 'Source file not readable.'];
                    $skipped++;
                    continue;
                }

                $ext = strtolower((string)pathinfo($relPath, PATHINFO_EXTENSION));
                if (!in_array($ext, self::ALLOWED_EXTENSIONS, true)) {
                    $errors[] = ['file_path' => $relPath, 'message' => 'Unsupported file extension: ' . $ext];
                    $skipped++;
                    continue;
                }

                $mime = $this->detectMimeType($sourceAbsPath);
                if ($mime === '' || !in_array($mime, self::ALLOWED_MIME_TYPES, true)) {
                    $errors[] = ['file_path' => $relPath, 'message' => 'Invalid MIME type: ' . ($mime !== '' ? $mime : 'unknown')];
                    $skipped++;
                    continue;
                }

                $roles = $this->resolveRoleTypes((string)($row['roles'] ?? ''));
                $position = max(1, (int)($row['position'] ?? 0));
                $requestedPath = $this->normalizeRelativePath($relPath);
                $sourceHash = $this->hashFileSafe($sourceAbsPath);
                if ($requestedPath !== '') {
                    $requestedPositions[$requestedPath] = $position;
                }
                if ($sourceHash !== '') {
                    if (!isset($requestedPositionsByHash[$sourceHash])) {
                        $requestedPositionsByHash[$sourceHash] = [];
                    }
                    $requestedPositionsByHash[$sourceHash][] = $position;
                }

                try {
                    $product->addImageToMediaGallery(
                        $sourceAbsPath,
                        $roles,
                        false,
                        false
                    );

                    $attached++;

                    if ($debugMode && count($debugRows) < 30) {
                        $debugRows[] = [
                            'status' => 'attached-via-model',
                            'file_path' => $relPath,
                            'source_path' => $sourceAbsPath,
                            'roles' => $roles,
                            'position' => $position,
                        ];
                    }
                } catch (\Throwable $attachError) {
                    $errors[] = [
                        'file_path' => $relPath,
                        'message' => $attachError->getMessage(),
                    ];

                    $this->logger->error(
                        '[ArtM\\MassProducts] AddImagesToProduct attach error: ' . $attachError->getMessage(),
                        ['sku' => $sku, 'file_path' => $relPath]
                    );

                    if ($debugMode && count($debugRows) < 30) {
                        $debugRows[] = [
                            'status' => 'attach-failed-model',
                            'file_path' => $relPath,
                            'message' => $attachError->getMessage(),
                        ];
                    }
                }
            }

            if ($attached > 0) {
                $this->productRepository->save($product);

                // Reload after gallery updates, then apply deterministic order and role fallback.
                $product = $this->productRepository->get($sku, true, null, true);
                $galleryEntries = $product->getMediaGalleryEntries();

                if (is_array($galleryEntries) && !empty($galleryEntries)) {
                    foreach ($requestedPositionsByHash as &$positions) {
                        sort($positions, SORT_NUMERIC);
                    }
                    unset($positions);

                    $sortableEntries = [];
                    foreach ($galleryEntries as $index => $entry) {
                        $file = (string)$entry->getFile();
                        $normalizedEntryFile = $this->normalizeRelativePath($file);
                        $currentPosition = max(1, (int)$entry->getPosition());

                        $weight = 100000 + $currentPosition + ($index / 1000);
                        $matched = false;

                        // Match attached gallery entries to source rows by file hash to avoid filename collisions.
                        $entryAbsPath = $this->resolveGalleryAbsolutePath($normalizedEntryFile);
                        $entryHash = $this->hashFileSafe($entryAbsPath);

                        if ($entryHash !== '' && isset($requestedPositionsByHash[$entryHash]) && !empty($requestedPositionsByHash[$entryHash])) {
                            $weight = (int)array_shift($requestedPositionsByHash[$entryHash]);
                            $matched = true;
                        }

                        // Fallback: match by normalized source relative path if hash was not matched.
                        if (!$matched && $normalizedEntryFile !== '') {
                            $catalogPrefix = 'catalog/product/';
                            $pos = strpos($normalizedEntryFile, $catalogPrefix);
                            if ($pos !== false) {
                                $suffixPath = substr($normalizedEntryFile, $pos + strlen($catalogPrefix));
                                $tempPrefix = 'artm_massproducts_temp/' . $draftId . '/';
                                $fallbackRelPath = $this->normalizeRelativePath($tempPrefix . ltrim((string)$suffixPath, '/'));

                                if (isset($requestedPositions[$fallbackRelPath])) {
                                    $weight = (int)$requestedPositions[$fallbackRelPath];
                                }
                            }
                        }

                        $sortableEntries[] = [
                            'weight' => $weight,
                            'index' => $index,
                            'entry' => $entry,
                        ];
                    }

                    usort($sortableEntries, static function (array $a, array $b): int {
                        if ($a['weight'] === $b['weight']) {
                            return $a['index'] <=> $b['index'];
                        }

                        return $a['weight'] <=> $b['weight'];
                    });

                    $galleryEntries = array_map(
                        static fn(array $item) => $item['entry'],
                        $sortableEntries
                    );

                    foreach ($galleryEntries as $index => $entry) {
                        $entry->setPosition($index + 1);
                    }

                    $first = reset($galleryEntries);
                    if ($first) {
                        $firstFile = (string)$first->getFile();
                        if ($firstFile !== '') {
                            $hasBase = (string)$product->getImage();
                            $hasSmall = (string)$product->getSmallImage();
                            $hasThumb = (string)$product->getThumbnail();

                            if ($hasBase === '' || $hasBase === 'no_selection') {
                                $product->setImage($firstFile);
                            }
                            if ($hasSmall === '' || $hasSmall === 'no_selection') {
                                $product->setSmallImage($firstFile);
                            }
                            if ($hasThumb === '' || $hasThumb === 'no_selection') {
                                $product->setThumbnail($firstFile);
                            }
                        }
                    }

                    $product->setMediaGalleryEntries($galleryEntries);
                    $this->productRepository->save($product);

                    if ($debugMode && count($debugRows) < 30) {
                        $debugRows[] = [
                            'status' => 'post-save-gallery-normalized',
                            'entries' => count($galleryEntries),
                            'requested_positions' => count($requestedPositions),
                        ];
                    }
                }
            }

            if ($attached <= 0) {
                return $result->setData([
                    'success' => false,
                    'message' => 'No images were attached to product.',
                    'attached' => 0,
                    'skipped' => $skipped,
                    'errors' => $errors,
                    'debug' => $debugMode ? $debugRows : null,
                ]);
            }

            return $result->setData([
                'success' => true,
                'message' => 'Images added to product successfully.',
                'sku' => $sku,
                'product_id' => $productId,
                'attached' => $attached,
                'skipped' => $skipped,
                'errors' => $errors,
                'debug' => $debugMode ? $debugRows : null,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('[ArtM\\MassProducts] AddImagesToProduct fatal: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
                'debug_message' => $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine(),
                'debug' => [
                    'draft_id' => (int)$this->getRequest()->getParam('draft_id', 0),
                    'sku' => trim((string)$this->getRequest()->getParam('sku', '')),
                    'product_id' => (int)$this->getRequest()->getParam('product_id', 0),
                    'exception_class' => get_class($e),
                    'exception_file' => $e->getFile(),
                    'exception_line' => $e->getLine(),
                ],
            ]);
        }
    }

    private function resolveRoleTypes(string $rolesCsv): array
    {
        $rolesRaw = array_values(array_filter(array_map('trim', explode(',', $rolesCsv))));
        $types = [];

        foreach ($rolesRaw as $role) {
            if ($role === 'base') {
                $types[] = 'image';
                continue;
            }
            if ($role === 'small') {
                $types[] = 'small_image';
                continue;
            }
            if ($role === 'thumbnail') {
                $types[] = 'thumbnail';
                continue;
            }
            if ($role === 'swatch' || $role === 'swatch_image') {
                $types[] = 'swatch_image';
                continue;
            }
            if ($role === 'swatch_thumbnail') {
                $types[] = 'swatch_thumbnail';
                continue;
            }
            if (in_array($role, ['image', 'small_image', 'thumbnail', 'swatch_image', 'swatch_thumbnail'], true)) {
                $types[] = $role;
            }
        }

        if (empty($types)) {
            $types = ['image', 'small_image', 'thumbnail', 'swatch_image', 'swatch_thumbnail'];
        }

        return array_values(array_unique($types));
    }

    private function detectMimeType(string $absolutePath): string
    {
        $mime = '';

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $absolutePath);
                finfo_close($finfo);
            }
        }

        if ($mime === '' && function_exists('mime_content_type')) {
            $mime = (string)mime_content_type($absolutePath);
        }

        return $mime;
    }

    private function normalizeRelativePath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));

        return ltrim($normalized, '/');
    }

    private function resolveGalleryAbsolutePath(string $entryFile): string
    {
        $normalized = '/' . ltrim(str_replace('\\', '/', $entryFile), '/');

        if (strpos($normalized, '/catalog/product/') === 0) {
            return BP . '/pub/media' . $normalized;
        }

        return BP . '/pub/media/catalog/product' . $normalized;
    }

    private function hashFileSafe(string $absolutePath): string
    {
        if ($absolutePath === '' || !is_file($absolutePath) || !is_readable($absolutePath)) {
            return '';
        }

        $hash = @hash_file('sha1', $absolutePath);
        return $hash === false ? '' : (string)$hash;
    }

}
