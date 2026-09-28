<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Success;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use ArtM\MassProducts\Model\MediaTableManager;

class UploadProductImage extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private ResourceConnection $resource;
    private Filesystem $filesystem;
    private MediaTableManager $mediaTableManager;
    private ProductRepositoryInterface $productRepository;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        Filesystem $filesystem,
        MediaTableManager $mediaTableManager,
        ProductRepositoryInterface $productRepository
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->filesystem = $filesystem;
        $this->mediaTableManager = $mediaTableManager;
        $this->productRepository = $productRepository;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $draftId = (int)$this->getRequest()->getParam('draft_id', 0);
            $productId = (int)$this->getRequest()->getParam('product_id', 0);
            $sku = trim((string)$this->getRequest()->getParam('sku', ''));
            if ($draftId <= 0) {
                return $result->setData(['success' => false, 'message' => 'Invalid draft id.']);
            }

            $connection = $this->resource->getConnection();
            $draftTable = $connection->getTableName('artm_massproducts_draft');

            if ($sku === '' && $connection->isTableExists($draftTable)) {
                $sku = trim((string)$connection->fetchOne(
                    $connection->select()->from($draftTable, ['sku'])->where('entity_id = ?', $draftId)
                ));
            }

            if ($sku === '' && $productId <= 0) {
                return $result->setData(['success' => false, 'message' => 'Missing SKU/product id for this row.']);
            }

            try {
                if ($sku !== '') {
                    $product = $this->productRepository->get($sku, true, null, true);
                } else {
                    $product = $this->productRepository->getById($productId, true, null, true);
                    $sku = (string)$product->getSku();
                }
            } catch (NoSuchEntityException $e) {
                return $result->setData(['success' => false, 'message' => 'Product with SKU does not exist.']);
            }

            if ($sku === '') {
                return $result->setData(['success' => false, 'message' => 'Unable to resolve SKU for product.']);
            }

            if (empty($_FILES['image']['tmp_name'])) {
                return $result->setData(['success' => false, 'message' => 'No file uploaded.']);
            }

            $tmpName = (string)$_FILES['image']['tmp_name'];
            if (!is_uploaded_file($tmpName)) {
                return $result->setData(['success' => false, 'message' => 'Invalid upload source.']);
            }

            $mimeType = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = (string)finfo_file($finfo, $tmpName);
                    finfo_close($finfo);
                }
            }
            if ($mimeType === '' && function_exists('mime_content_type')) {
                $mimeType = (string)mime_content_type($tmpName);
            }

            if ($mimeType === '') {
                $ext = strtolower((string)pathinfo((string)($_FILES['image']['name'] ?? ''), PATHINFO_EXTENSION));
                $map = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                ];
                $mimeType = $map[$ext] ?? '';
            }

            if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
                return $result->setData(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, JPEG, PNG.']);
            }

            $origName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)($_FILES['image']['name'] ?? 'upload')));
            $origName = ltrim((string)$origName, '.');
            if ($origName === '') {
                $origName = 'image';
            }

            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $subDir = 'artm_massproducts_temp/' . $draftId;
            $mediaDir->create($subDir);

            $fileName = uniqid('img_', true) . '_' . $origName;
            $relPath = $subDir . '/' . $fileName;
            $absPath = $mediaDir->getAbsolutePath($relPath);

            if (!move_uploaded_file($tmpName, $absPath) && !@copy($tmpName, $absPath)) {
                return $result->setData(['success' => false, 'message' => 'Failed to save uploaded file.']);
            }

            $table = $this->mediaTableManager->ensureExists();
            $maxPos = (int)$connection->fetchOne(
                $connection->select()->from($table, ['MAX(position)'])->where('draft_id = ?', $draftId)
            );

            $connection->insert($table, [
                'draft_id' => $draftId,
                'file_path' => $relPath,
                'roles' => '',
                'position' => $maxPos + 1,
            ]);

            $mediaId = (int)$connection->lastInsertId($table);

            // Directly attach uploaded image to existing product with required image roles.
            try {
                $product->addImageToMediaGallery(
                    $absPath,
                    ['image', 'small_image', 'thumbnail', 'swatch_image', 'swatch_thumbnail'],
                    false,
                    false
                );
                $this->productRepository->save($product);
            } catch (\Throwable $attachError) {
                return $result->setData([
                    'success' => false,
                    'message' => 'Image uploaded to temp, but failed to attach to product: ' . $attachError->getMessage(),
                ]);
            }

            $url = rtrim($this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]), '/') . '/' . ltrim($relPath, '/');

            return $result->setData([
                'success' => true,
                'message' => 'Image uploaded and attached to product by SKU.',
                'draft_id' => $draftId,
                'media_id' => $mediaId,
                'sku' => $sku,
                'url' => $url,
                'file_path' => $relPath,
                'roles' => [],
            ]);
        } catch (\Throwable $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
