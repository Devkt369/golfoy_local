<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Giftvoucher\Model;

use Exception;
use Magento\Framework\Api\Data\ImageContentInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ImageContentValidatorInterface;
use Magento\Framework\Api\Uploader;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Phrase;
use Psr\Log\LoggerInterface;
use Magestore\Giftvoucher\Api\ImageProcessorInterface;
use Magestore\Giftvoucher\Helper\Data as GiftCardHelper;
use Magento\Framework\Filesystem\Io\File as IoFile;

/**
 * Giftvoucher - Image Processor
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ImageProcessor implements ImageProcessorInterface
{
    const DESTINATION_DIRECTORY = 'tmp/giftvoucher/images';

    /**
     * MIME type/extension map
     *
     * @var array
     */
    protected $mimeTypeExtensionMap = [
        'image/jpg' => 'jpg',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/png' => 'png',
    ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Filesystem
     */
    private $contentValidator;

    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Uploader
     */
    private $uploader;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @var GiftCardHelper
     */
    private $giftCardHelper;

    /**
     * ImageProcessor constructor.
     *
     * @param Filesystem $fileSystem
     * @param ImageContentValidatorInterface $contentValidator
     * @param DataObjectHelper $dataObjectHelper
     * @param LoggerInterface $logger
     * @param Uploader $uploader
     * @param GiftCardHelper $giftCardHelper
     * @throws FileSystemException
     */
    public function __construct(
        Filesystem $fileSystem,
        ImageContentValidatorInterface $contentValidator,
        DataObjectHelper $dataObjectHelper,
        LoggerInterface $logger,
        Uploader $uploader,
        GiftCardHelper $giftCardHelper
    ) {
        $this->filesystem = $fileSystem;
        $this->contentValidator = $contentValidator;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->logger = $logger;
        $this->uploader = $uploader;
        $this->giftCardHelper = $giftCardHelper;
        $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
    }

    /**
     * @inheritdoc
     */
    public function processImageContent(ImageContentInterface $imageContent)
    {
        if (!$this->contentValidator->isValid($imageContent)) {
            throw new InputException(new Phrase('The image content is invalid. Verify the content and try again.'));
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction, Generic.PHP.NoSilencedErrors
        $fileContent = @base64_decode($imageContent->getBase64EncodedData(), true);
        $tmpDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::SYS_TMP);
        $fileName = $this->getFileName($imageContent);
        $tmpFileName = substr(sha1(rand()), 0, 7) . '.' . $fileName;
        $tmpDirectory->writeFile($tmpFileName, $fileContent);

        $fileAttributes = [
            'tmp_name' => $tmpDirectory->getAbsolutePath() . $tmpFileName,
            'name' => $imageContent->getName()
        ];

        try {
            $this->uploader->processFileAttributes($fileAttributes);
            $this->uploader->setFilesDispersion(false);
            $this->uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $this->uploader->setAllowRenameFiles(true);
            $this->uploader->save(
                $this->mediaDirectory->getAbsolutePath(self::DESTINATION_DIRECTORY),
                $fileName
            );

            $path = trim('tmp/giftvoucher/images/' . $this->uploader->getUploadedFileName());
            return $this->giftCardHelper->getStoreManager()->getStore()
                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA)
                . $path;
        } catch (Exception $e) {
            $this->logger->critical($e);
            return '';
        }
    }

    /**
     * Get mine type extension for image
     *
     * @param string $mimeType
     * @return string
     */
    protected function getMimeTypeExtension($mimeType)
    {
        return $this->mimeTypeExtensionMap[$mimeType] ?? '';
    }

    /**
     * Get file name of image
     *
     * @param ImageContentInterface $imageContent
     * @return string
     * @throws LocalizedException
     */
    private function getFileName($imageContent)
    {
        $fileName = $imageContent->getName();
        $om = ObjectManager::getInstance();
        /** @var IoFile $ioFile */
        $ioFile = $om->get(IoFile::class);
        $fileInfo = $ioFile->getPathInfo($fileName);
        $ext = $fileInfo['extension'] ?: '';
        if (!$ext) {
            if (!$imageContent->getType() || !$this->getMimeTypeExtension($imageContent->getType())) {
                throw new InputException(new Phrase('Cannot recognize image extension.'));
            }
            $fileName .= '.' . $this->getMimeTypeExtension($imageContent->getType());
        }
        return $fileName;
    }
}
