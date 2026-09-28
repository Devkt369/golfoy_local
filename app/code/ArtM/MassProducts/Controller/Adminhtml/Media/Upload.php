<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Media;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use ArtM\MassProducts\Model\MediaTableManager;

class Upload extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private ResourceConnection $resource;
    private Filesystem $filesystem;
    private MediaTableManager $mediaTableManager;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        Filesystem $filesystem,
        MediaTableManager $mediaTableManager
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->filesystem = $filesystem;
        $this->mediaTableManager = $mediaTableManager;
    }

    public function execute(): Json
    {
        /** @var Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $draftId = (int)$this->getRequest()->getParam('draft_id', 0);
            if ($draftId <= 0) {
                return $result->setData(['success' => false, 'message' => 'Invalid draft ID.']);
            }

            if (empty($_FILES['image']['tmp_name'])) {
                return $result->setData(['success' => false, 'message' => 'No file uploaded.']);
            }

            $tmpName = $_FILES['image']['tmp_name'];

            // Validate MIME type via finfo – never trust browser-supplied type
            if (!is_uploaded_file($tmpName)) {
                return $result->setData(['success' => false, 'message' => 'Invalid upload.']);
            }

            $mimeType = '';
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $mimeType = (string)finfo_file($finfo, $tmpName);
                    finfo_close($finfo);
                }
            } elseif (function_exists('mime_content_type')) {
                $mimeType = (string)mime_content_type($tmpName);
            }

            if ($mimeType === '') {
                $ext = strtolower((string)pathinfo((string)($_FILES['image']['name'] ?? ''), PATHINFO_EXTENSION));
                $extToMime = [
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'png' => 'image/png',
                ];
                $mimeType = $extToMime[$ext] ?? '';
            }

            $allowedMimes = ['image/jpeg', 'image/png'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                return $result->setData(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, JPEG, PNG.']);
            }

            // Sanitize original filename and build a unique destination name
            $origName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename((string)($_FILES['image']['name'] ?? 'upload')));
            $origName = ltrim($origName, '.');
            if ($origName === '') {
                $origName = 'image';
            }
            $filename = uniqid('img_', true) . '_' . $origName;
            $subDir   = 'artm_massproducts_temp/' . $draftId;

            $mediaDir = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $mediaDir->create($subDir);

            $relPath = $subDir . '/' . $filename;
            $absPath = $mediaDir->getAbsolutePath($relPath);

            if (!move_uploaded_file($tmpName, $absPath) && !@copy($tmpName, $absPath)) {
                return $result->setData(['success' => false, 'message' => 'Failed to save uploaded file.']);
            }

            // Persist record
            $connection = $this->resource->getConnection();
            $table      = $this->mediaTableManager->ensureExists();

            $maxPos = (int)$connection->fetchOne(
                $connection->select()->from($table, ['MAX(position)'])->where('draft_id = ?', $draftId)
            );

            $connection->insert($table, [
                'draft_id'  => $draftId,
                'file_path' => $relPath,
                'roles'     => '',
                'position'  => $maxPos + 1,
            ]);
            $mediaId = (int)$connection->lastInsertId($table);

            $mediaBaseUrl = $this->_url->getBaseUrl(['_type' => UrlInterface::URL_TYPE_MEDIA]);
            $url          = rtrim($mediaBaseUrl, '/') . '/' . ltrim($relPath, '/');

            return $result->setData([
                'success'    => true,
                'media_id'   => $mediaId,
                'file_path'  => $relPath,
                'url'        => $url,
                'roles'      => [],
            ]);
        } catch (\Throwable $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
