<?php

namespace ArtM\RecommendedSort\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem\Io\File;
use ArtM\RecommendedSort\Logger\SortingLogLogger;

class Download extends Action
{
    protected $resultRawFactory;
    protected $fileIo;
    protected $logger;

    public function __construct(
        Action\Context $context,
        RawFactory $resultRawFactory,
        File $fileIo,
        SortingLogLogger $logger
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->fileIo = $fileIo;
        $this->logger = $logger;
    }

    public function execute()
    {
        $batchid = $this->getRequest()->getParam('id');
        // print_r($fileName);die;

        $fileName = $this->getRequest()->getParam('file');
        $logPath = BP . '/var/log/SortLog/' . basename($fileName);

        if (!$this->fileIo->fileExists($logPath)) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Log file does not exist.'));
        }

        $resultRaw = $this->resultRawFactory->create();
        $content = $this->fileIo->read($logPath);
        $this->logger->info("Log for Batch id  {$batchid} downloaded successfully");
        return $resultRaw->setContents($content)
            ->setHeader('Content-Type', 'text/plain')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
}
