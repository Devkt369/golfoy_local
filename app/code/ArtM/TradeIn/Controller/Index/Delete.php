<?php

namespace ArtM\TradeIn\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Io\File;

class Delete extends \Magento\Framework\App\Action\Action
{
    protected $resultJsonFactory;
    protected $fileIo;

    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->fileIo = $fileIo;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();

        $fileName = $this->getRequest()->getParam('filename');

        if (!$fileName) {
            return $result->setData([
                'error' => true,
                'message' => 'Filename is missing.'
            ]);
        }

        $mediaPath = BP . '/pub/media/tradein_invoice_upload/' . basename($fileName);

        if (is_file($mediaPath)) {
            try {
                $this->fileIo->rm($mediaPath);
                return $result->setData([
                    'success' => true,
                    'message' => 'File deleted.'
                ]);
            } catch (\Exception $e) {
                return $result->setData([
                    'error' => true,
                    'message' => $e->getMessage()
                ]);
            }
        }

        return $result->setData([
            'error' => true,
            'message' => 'File not found.'
        ]);
    }
}
