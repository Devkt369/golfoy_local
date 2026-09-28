<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Media;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem;
use ArtM\Common\Model\ImageCompressor;

class Ajaxupload extends Action
{
    protected $uploaderFactory;
    protected $filesystem;
    protected $resultJsonFactory;
    protected $imageCompressor;

    public function __construct(
        Action\Context $context,
        JsonFactory $resultJsonFactory,
        UploaderFactory $uploaderFactory,
        Filesystem $filesystem,
        ImageCompressor $imageCompressor
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->uploaderFactory = $uploaderFactory;
        $this->filesystem = $filesystem;
        $this->imageCompressor = $imageCompressor;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        // try {

            $uploader = $this->uploaderFactory->create(['fileId' => 'files']);

            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'png']);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            $mediaDirectory = $this->filesystem
                ->getDirectoryRead(DirectoryList::MEDIA)
                ->getAbsolutePath();

            $targetPath = $mediaDirectory . 'temp/product_media/';

            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0777, true);
            }

            $resultData = $uploader->save($targetPath);

            if (!$resultData) {
                throw new \Exception('File could not be saved.');
            }

            $filePath = $targetPath . $resultData['file'];

            return $result->setData([
                'success' => true,
                'path' => '/temp/product_media/' . $resultData['file'],
                'name' => $resultData['name']
            ]);

        // } catch (\Exception $e) {

        //     return $result->setData([
        //         'success' => false,
        //         'error' => $e->getMessage()
        //     ]);
        // }
    }

    // 🔐 VERY IMPORTANT (ACL)
    protected function _isAllowed()
    {
        return true; // or use ACL resource
    }
}