<?php

namespace ArtM\PageGroups\Controller\Adminhtml\Media;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\MediaStorage\Model\File\UploaderFactory;

class Upload extends AbstractMedia
{
    /**
     * @var UploaderFactory
     */
    private $uploaderFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\Driver\File $fileDriver,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        UploaderFactory $uploaderFactory
    ) {
        parent::__construct($context, $resultJsonFactory, $filesystem, $fileDriver, $storeManager);
        $this->uploaderFactory = $uploaderFactory;
    }

    /**
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $resultJson = $this->jsonResult();
        $path = $this->normalizePath((string)$this->getRequest()->getParam('path', ''));

        try {
            if (empty($_FILES['image']['name'])) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('No image file was uploaded.'),
                ]);
            }

            $targetRelative = $this->relativeToRoot($path);
            $this->mediaDirectory->create($targetRelative);

            $uploader = $this->uploaderFactory->create(['fileId' => 'image']);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $result = $uploader->save($this->mediaDirectory->getAbsolutePath($targetRelative));
            if (!$result) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Unable to upload image.'),
                ]);
            }

            $relativeFilePath = ltrim(($path ? $path . '/' : '') . $result['file'], '/');

            return $resultJson->setData([
                'success' => true,
                'message' => __('Image uploaded successfully.'),
                'item' => [
                    'name' => $result['file'],
                    'path' => $relativeFilePath,
                    'url' => $this->mediaUrl($relativeFilePath),
                ]
            ]);
        } catch (\Throwable $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to upload image: %1', $e->getMessage()),
            ]);
        }
    }
}
