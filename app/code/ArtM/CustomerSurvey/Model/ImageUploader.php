<?php

namespace ArtM\CustomerSurvey\Model;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Helper\File\Storage\Database;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Store\Model\StoreManagerInterface;

class ImageUploader
{
    private const BASE_TMP_PATH = 'customersurvey/location/tmp';
    private const BASE_PATH = 'customersurvey/location';

    /**
     * @var Database
     */
    private $coreFileStorageDatabase;

    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        Database $coreFileStorageDatabase,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager
    ) {
        $this->coreFileStorageDatabase = $coreFileStorageDatabase;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $fileId
     * @return array<string, mixed>
     * @throws LocalizedException
     */
    public function saveFileToTmpDir(string $fileId): array
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $target = $mediaDirectory->getAbsolutePath(self::BASE_TMP_PATH);

        try {
            $uploader = $this->uploaderFactory->create(['fileId' => $fileId]);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'webp']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $result = $uploader->save($target);
            if (!$result) {
                throw new LocalizedException(__('File cannot be saved to path: %1', $target));
            }
        } catch (\Exception $exception) {
            throw new LocalizedException(__($exception->getMessage()));
        }

        if (isset($result['file'])) {
            $this->coreFileStorageDatabase->saveFile(self::BASE_TMP_PATH . '/' . ltrim($result['file'], '/'));
        }

        $result['name'] = $result['file'];
        $result['tmp_name'] = self::BASE_TMP_PATH . '/' . ltrim((string)$result['file'], '/');
        $result['url'] = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . self::BASE_TMP_PATH . '/' . ltrim((string)$result['file'], '/');

        return $result;
    }

    /**
     * @param string $imageName
     * @return string
     * @throws LocalizedException
     */
    public function moveFileFromTmp(string $imageName): string
    {
        $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $tmpPath = self::BASE_TMP_PATH . '/' . ltrim($imageName, '/');
        $basePath = self::BASE_PATH . '/' . ltrim($imageName, '/');

        try {
            $this->coreFileStorageDatabase->copyFile($tmpPath, $basePath);
            $mediaDirectory->renameFile($tmpPath, $basePath);
        } catch (\Exception $exception) {
            throw new LocalizedException(__('Something went wrong while saving the file.'));
        }

        return $imageName;
    }

    public function getMediaUrl(string $imageName): string
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
            . self::BASE_PATH . '/' . ltrim($imageName, '/');
    }
}
