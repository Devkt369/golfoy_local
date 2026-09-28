<?php

declare(strict_types=1);

namespace ArtM\ProductImageApi\Model;

use ArtM\ProductImageApi\Api\Data\ProductImageResponseInterface;
use ArtM\ProductImageApi\Api\Data\ProductImageResponseInterfaceFactory;
use ArtM\ProductImageApi\Api\ProductImageManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Gallery\CreateHandler as GalleryCreateHandler;
use Magento\Catalog\Model\ResourceModel\Product as ProductResourceModel;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\Product\Image\Cache as ImageCache;
use Magento\Catalog\Model\Product\Image\CacheFactory as ImageCacheFactory;

/**
 * Class ProductImageManagement
 *
 * All changes applied:
 *  1. Dynamic media path  — imageNames are relative to pub/media/ (no hardcoded subdir)
 *  2. No clear           — existing gallery images are kept, new ones are appended
 *  3. Fast save          — bypasses ProductRepository::save() using direct resource model writes
 *  4. Source cleanup     — source files deleted from import dir after successful save
 *
 * First image in array → base_image, small_image, thumbnail
 * Other images         → gallery only (no roles)
 */
class ProductImageManagement implements ProductImageManagementInterface
{
    private const PRIMARY_ROLES       = ['image', 'small_image', 'thumbnail'];
    private const ALLOWED_EXTENSIONS  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private ProductRepositoryInterface $productRepository;
    private ProductImageResponseInterfaceFactory $responseFactory;
    private Filesystem $filesystem;
    private FileDriver $fileDriver;
    private StoreManagerInterface $storeManager;
    private LoggerInterface $logger;
    private ProductResourceModel $productResourceModel;
    private GalleryCreateHandler $galleryCreateHandler;
    private IndexerRegistry $indexerRegistry;
    private ImageCacheFactory $imageCacheFactory;
    private ImageCache $imageCache;

    /**
     * Indexers that would fire on full product save — we suspend them
     * for the duration of the image-only update then restore their state.
     */
    private const INDEXERS_TO_SUSPEND = [
        'catalog_category_product',
        'catalog_product_category',
        'catalog_product_price',
        'catalog_product_attribute',
        'catalogsearch_fulltext',
        'catalogrule_product',
    ];

    public function __construct(
        ProductRepositoryInterface           $productRepository,
        ProductImageResponseInterfaceFactory $responseFactory,
        Filesystem                           $filesystem,
        FileDriver                           $fileDriver,
        StoreManagerInterface                $storeManager,
        LoggerInterface                      $logger,
        ProductResourceModel                 $productResourceModel,
        GalleryCreateHandler                 $galleryCreateHandler,
        IndexerRegistry                      $indexerRegistry,
        ImageCacheFactory                    $imageCacheFactory,
        ImageCache                           $imageCache
    ) {
        $this->productRepository    = $productRepository;
        $this->responseFactory      = $responseFactory;
        $this->filesystem           = $filesystem;
        $this->fileDriver           = $fileDriver;
        $this->storeManager         = $storeManager;
        $this->logger               = $logger;
        $this->productResourceModel = $productResourceModel;
        $this->galleryCreateHandler = $galleryCreateHandler;
        $this->indexerRegistry      = $indexerRegistry;
        $this->imageCacheFactory    = $imageCacheFactory;
        $this->imageCache           = $imageCache;
    }

    /**
     * {@inheritdoc}
     */
    public function setProductImage(string $sku, array $imageNames): ProductImageResponseInterface
    {
        /** @var ProductImageResponseInterface $response */
        $response = $this->responseFactory->create();
        $response->setSku($sku);

        try {
            if (empty($imageNames)) {
                throw new LocalizedException(__('imageNames array must not be empty.'));
            }

            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);

            // ------------------------------------------------------------------
            // 1. Resolve & validate every source file before touching the product
            //    imageNames are relative to pub/media/
            //    e.g. "import/product_images/1.jpg" or "import/banners/2.jpg"
            // ------------------------------------------------------------------
            $resolvedPaths = [];
            foreach ($imageNames as $imageName) {
                $sourcePath = $mediaDirectory->getAbsolutePath(ltrim($imageName, '/'));
                $this->validateFile($sourcePath, $imageName);
                $resolvedPaths[] = $sourcePath;
            }

            // ------------------------------------------------------------------
            // 2. Load product in admin scope (edit mode for gallery access)
            // ------------------------------------------------------------------
            /** @var Product $product */
            $product = $this->productRepository->get($sku, true, 0, true);

            // ------------------------------------------------------------------
            // 3. Copy each image into catalog media dir & build gallery entries
            //    No existing images cleared — new ones are appended
            // ------------------------------------------------------------------
            $imageUrls        = [];
            $isFirstNewImage  = true;

            foreach ($resolvedPaths as $sourcePath) {
                $mediaRelativePath   = $this->copyImageToMediaDirectory($sourcePath);
                $absoluteDestination = $mediaDirectory->getAbsolutePath(
                    'catalog/product' . $mediaRelativePath
                );

                // First image in this request → primary roles
                // All others → gallery only
                $roles = $isFirstNewImage ? self::PRIMARY_ROLES : [];

                $product->addImageToMediaGallery(
                    $absoluteDestination,
                    $roles,
                    false,  // do not move — already copied
                    false   // visible in gallery
                );

                $imageUrls[]     = $this->buildPublicUrl($mediaRelativePath);
                $isFirstNewImage = false;
            }

            // ------------------------------------------------------------------
            // 4. Fast save — write directly to gallery resource model
            //    Bypasses ProductRepository::save() so no indexers fire,
            //    no observers run, no cache flush — reduces time from ~20s to ~2s
            // ------------------------------------------------------------------
            $this->saveGalleryDirectly($product);

            // $imageCache = $this->imageCacheFactory->create();
            // $imageCache->generate($product);

            // ------------------------------------------------------------------
            // 5. Delete source files from import directory after successful save
            //    If deletion fails we log a warning but keep success = true
            // ------------------------------------------------------------------
            foreach ($resolvedPaths as $sourcePath) {
                try {
                    if ($this->fileDriver->isExists($sourcePath)) {
                        $this->fileDriver->deleteFile($sourcePath);
                    }
                } catch (\Exception $e) {
                    $this->logger->warning(
                        '[ArtM_ProductImageApi] Could not delete source file: ' . $sourcePath,
                        ['error' => $e->getMessage()]
                    );
                }
            }

            $response->setSuccess(true)
                ->setMessage(
                    sprintf(
                        '%d image(s) added for SKU "%s". First image assigned roles: %s.',
                        count($imageUrls),
                        $sku,
                        implode(', ', self::PRIMARY_ROLES)
                    )
                )
                ->setImageUrl($imageUrls[0])
                ->setImageUrls($imageUrls);

        } catch (NoSuchEntityException $e) {
            $this->logger->error('[ArtM_ProductImageApi] Product not found: ' . $e->getMessage());
            $response->setSuccess(false)
                ->setMessage(sprintf('Product with SKU "%s" was not found.', $sku));

        } catch (LocalizedException $e) {
            $this->logger->error('[ArtM_ProductImageApi] Error: ' . $e->getMessage());
            $response->setSuccess(false)
                ->setMessage($e->getMessage());

        } catch (\Exception $e) {
            $this->logger->critical('[ArtM_ProductImageApi] Unexpected: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            $response->setSuccess(false)
                ->setMessage('An unexpected error occurred. Check Magento logs for details.');
        }

        return $response;
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Save gallery and role attributes while suspending heavy indexers.
     *
     * Strategy:
     *  1. Temporarily set all heavy indexers to "scheduled" mode so they
     *     do NOT execute synchronously during save.
     *  2. Use GalleryCreateHandler to write gallery rows directly — this
     *     is the same handler Magento uses internally, so it is version-safe.
     *  3. Save only the three role attributes via ProductResourceModel.
     *  4. Restore every indexer back to its original mode.
     *
     * Result: ~2s instead of ~20s. Images appear immediately in admin.
     * Storefront search/price indexes update on next scheduled cron run.
     *
     * @param  Product $product
     * @throws \Exception
     */
    private function saveGalleryDirectly(Product $product): void
    {
        // ---- 1. Suspend indexers & record original modes -----------------
        $originalModes = [];
        // foreach (self::INDEXERS_TO_SUSPEND as $indexerId) {
        //     try {
        //         $indexer = $this->indexerRegistry->get($indexerId);
        //         $originalModes[$indexerId] = $indexer->isScheduled();
        //         $indexer->setScheduled(true); // switch to "Update by Schedule"
        //     } catch (\Exception $e) {
        //         // Indexer may not exist in all Magento editions — skip silently
        //     }
        // }

        try {
            // ---- 2. Write gallery entries directly via Magento's own handler
            //         Works on all Magento 2.3+ versions — no undefined method risk
            $this->galleryCreateHandler->execute($product);

            // ---- 3. Save only the three role attributes — no full product save
            foreach (self::PRIMARY_ROLES as $role) {
                if ($product->getData($role)) {
                    $this->productResourceModel->saveAttribute($product, $role);
                }
            }
        } finally {
            // ---- 4. Always restore original indexer modes even if save fails
            // foreach ($originalModes as $indexerId => $wasScheduled) {
            //     try {
            //         $this->indexerRegistry->get($indexerId)->setScheduled($wasScheduled);
            //     } catch (\Exception $e) {
            //         // ignore
            //     }
            // }
        }
    }

    /**
     * Validate that the source file exists, is readable, and has an allowed extension.
     *
     * @param  string $absolutePath  Full resolved path to file on disk.
     * @param  string $displayName   Original relative path shown in error messages.
     * @throws LocalizedException
     */
    private function validateFile(string $absolutePath, string $displayName): void
    {
        if (!$this->fileDriver->isExists($absolutePath)) {
            throw new LocalizedException(
                __('Image file not found in media directory: "%1"', $displayName)
            );
        }

        if (!$this->fileDriver->isReadable($absolutePath)) {
            throw new LocalizedException(
                __('Image file is not readable: "%1"', $displayName)
            );
        }

        $extension = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new LocalizedException(
                __(
                    'Extension ".%1" not allowed for "%2". Allowed: %3',
                    $extension,
                    $displayName,
                    implode(', ', self::ALLOWED_EXTENSIONS)
                )
            );
        }
    }

    /**
     * Copy source image into pub/media/catalog/product/<x>/<y>/
     * using Magento's two-level sub-directory naming convention.
     *
     * @param  string $sourcePath  Absolute path to the source image.
     * @return string              Relative path as stored in Magento DB e.g. "/1/i/image.jpg"
     * @throws LocalizedException
     */
    private function copyImageToMediaDirectory(string $sourcePath): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $fileName       = pathinfo($sourcePath, PATHINFO_BASENAME);

        $firstChar  = strtolower(substr($fileName, 0, 1)) ?: 'a';
        $secondChar = strtolower(substr($fileName, 1, 1)) ?: $firstChar;
        $subDir     = sprintf('catalog/product/%s/%s', $firstChar, $secondChar);

        $mediaDirectory->create($subDir);

        // Resolve collision-safe destination filename
        $destinationRelative = $subDir . '/' . $fileName;
        $counter             = 1;
        while ($mediaDirectory->isExist($destinationRelative)) {
            $name                = pathinfo($fileName, PATHINFO_FILENAME);
            $ext                 = pathinfo($fileName, PATHINFO_EXTENSION);
            $destinationRelative = sprintf('%s/%s_%d.%s', $subDir, $name, $counter, $ext);
            $counter++;
        }

        $absoluteDestination = $mediaDirectory->getAbsolutePath($destinationRelative);

        if (!copy($sourcePath, $absoluteDestination)) {
            throw new LocalizedException(
                __('Could not copy "%1" to media directory.', $fileName)
            );
        }

        return '/' . $firstChar . '/' . $secondChar . '/' . pathinfo($destinationRelative, PATHINFO_BASENAME);
    }

    /**
     * Build the public-facing URL for the stored image.
     *
     * @param  string $mediaImagePath  e.g. "/1/i/image.jpg"
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function buildPublicUrl(string $mediaImagePath): string
    {
        $baseMediaUrl = $this->storeManager
            ->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        return rtrim($baseMediaUrl, '/') . '/catalog/product' . $mediaImagePath;
    }
}
