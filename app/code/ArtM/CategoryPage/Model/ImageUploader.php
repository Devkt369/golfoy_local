<?php
namespace ArtM\CategoryPage\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;

class ImageUploader
{
    private $uploaderFactory;
    private $filesystem;
    private $storeManager;

    public function __construct(
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    public function saveUploadedFile(string $fileId, string $subdir): array
    {
        if (trim($fileId) === '') {
            throw new LocalizedException(__('No file uploaded.'));
        }

        $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
        $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $targetPath = 'artm/categorypage/' . trim($subdir, '/');
        if (!$mediaDirectory->isExist($targetPath)) {
            $mediaDirectory->create($targetPath);
        }

        $result = $uploader->save($mediaDirectory->getAbsolutePath($targetPath));
        if (!is_array($result) || empty($result['file'])) {
            throw new LocalizedException(__('Unable to save uploaded file.'));
        }

        $relativePath = trim($targetPath, '/') . '/' . ltrim((string)$result['file'], '/');
        $mediaBaseUrl = rtrim((string)$this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA), '/');

        return [
            'path' => $relativePath,
            'url' => $mediaBaseUrl . '/' . $relativePath,
            'name' => (string)($result['file'] ?? ''),
        ];
    }
}
