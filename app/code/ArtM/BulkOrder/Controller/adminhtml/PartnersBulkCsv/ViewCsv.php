<?php

namespace ArtM\BulkOrder\Controller\Adminhtml\PartnersBulkCsv;

use Magento\Backend\App\Action;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\Filesystem\Io\File;

class ViewCsv extends Action
{
    protected $resultRawFactory;
    protected $directoryList;
    protected $fileIo;
    protected $logger;

    public function __construct(
        Action\Context $context,
        DirectoryList $directoryList,
        RawFactory $resultRawFactory,
        File $fileIo,
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->directoryList = $directoryList;
        $this->fileIo = $fileIo;
    }
    /**
     * Execute the download action
     *
     * @return \Magento\Framework\Controller\Result\Raw
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $file = $this->getRequest()->getParam('file') ?? '';
        if (!$file) {
            throw new NotFoundException(__('File not found.'));
        }
        // Assuming your CSVs are stored in var/bulkorder/
        $filePath = $this->directoryList->getPath('var') . '/' . $file;

        // var_dump($filePath); // Debugging line to check the file parameter
        // die;
        if (!file_exists($filePath)) {
            throw new NotFoundException(__('File not found.'));
        }

        // Read CSV
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }

        // Build simple HTML table to preview
        $html = '<h2>CSV Preview: ' . htmlspecialchars($file) . '</h2>';
        $html .= '<table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; max-width: 720px;">';
 
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';

        /** @var \Magento\Framework\Controller\Result\Raw $resultRaw */
        $resultRaw = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_RAW);
        $resultRaw->setHeader('Content-Type', 'text/html');
        $resultRaw->setContents($html);
        return $resultRaw;
    }

}
