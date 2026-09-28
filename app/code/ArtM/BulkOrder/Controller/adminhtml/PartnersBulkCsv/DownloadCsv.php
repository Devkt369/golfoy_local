<?php

namespace ArtM\BulkOrder\Controller\Adminhtml\PartnersBulkCsv;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem\Io\File;

class DownloadCsv extends Action
{
    protected $resultRawFactory;
    protected $fileIo;
    protected $logger;

    public function __construct(
        Action\Context $context,
        RawFactory $resultRawFactory,
        File $fileIo
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->fileIo = $fileIo;
    }

    public function execute()
    {
        $fileName = $this->getRequest()->getParam('file');
        $logPath = BP . '/var/bulkorder/' . basename($fileName);
        
        if (!$this->fileIo->fileExists($logPath)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Log file does not exist.'));
        }

        $resultRaw = $this->resultRawFactory->create();
        $content = $this->fileIo->read($logPath);
        return $resultRaw->setContents($content)
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
