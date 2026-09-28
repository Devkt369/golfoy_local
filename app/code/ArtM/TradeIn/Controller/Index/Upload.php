<?php

namespace ArtM\TradeIn\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Filesystem\DirectoryList;
use ArtM\Common\Model\ImageCompressor;

class Upload extends Action
{
    protected $resultJsonFactory;
    protected $uploaderFactory;
    protected $filesystem;
    protected $imageCompressor;
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
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
        try {
            $uploader = $this->uploaderFactory->create(['fileId' => 'invoice_upload']);
            $uploader->setAllowedExtensions(['pdf', 'jpg', 'jpeg', 'png','JPG', 'JPEG', 'PNG', 'PDF']);
            $uploader->setAllowRenameFiles(true);
            // Remove unsupported method for compatibility
            // $uploader->imagecreatefrompng(false); 

			// Pre-save compression: compress the temp uploaded image before saving to media
			if (isset($_FILES['invoice_upload']['tmp_name']) && is_uploaded_file($_FILES['invoice_upload']['tmp_name'])) {
				$this->imageCompressor->maybeCompressImage($_FILES['invoice_upload']['tmp_name']);
			}

            $mediaDirectory = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
            $target = $mediaDirectory->getAbsolutePath('tradein_invoice_upload/');

            $result = $uploader->save($target);

            return $this->resultJsonFactory->create()->setData([
                'success' => true,
                'file' => $result['file'],
                'url' => $this->_url->getBaseUrl(['_type' => \Magento\Framework\UrlInterface::URL_TYPE_MEDIA]) . 'tradein_invoice_upload/' . $result['file']
            ]);
        } catch (\Exception $e) {
            return $this->resultJsonFactory->create()->setData([
                'error' => true,
                'message' => $e->getMessage()
            ]);
        }
    }
}
