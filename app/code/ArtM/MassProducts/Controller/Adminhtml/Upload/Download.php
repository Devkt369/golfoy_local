<?php
namespace ArtM\MassProducts\Controller\Adminhtml\Upload;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResourceConnection;

class Download extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_MassProducts::mass_upload_product';

    private $resource;
    private $directoryList;
    private $fileFactory;

    public function __construct(
        Context $context,
        ResourceConnection $resource,
        DirectoryList $directoryList,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->resource = $resource;
        $this->directoryList = $directoryList;
        $this->fileFactory = $fileFactory;
    }

    public function execute()
    {
        $uploadId = (int)$this->getRequest()->getParam('upload_id', 0);
        if ($uploadId <= 0) {
            return $this->_redirect('massproducts/index/index');
        }

        try {
            $connection = $this->resource->getConnection();
            $uploadsTable = $connection->getTableName('artm_intake_uploads');
            $upload = $connection->fetchRow(
                $connection->select()->from($uploadsTable)->where('id = ?', $uploadId)
            );

            if (!$upload || empty($upload['filename'])) {
                return $this->_redirect('massproducts/index/index');
            }

            $filename = (string)$upload['filename'];
            $baseDir = rtrim($this->directoryList->getPath(DirectoryList::VAR_DIR), DIRECTORY_SEPARATOR)
                . DIRECTORY_SEPARATOR . 'artm_massupload' . DIRECTORY_SEPARATOR;
            $fullPath = $baseDir . $filename;

            if (!is_file($fullPath)) {
                return $this->_redirect('massproducts/index/index');
            }

            return $this->fileFactory->create(
                $filename,
                [
                    'type' => 'filename',
                    'value' => 'artm_massupload/' . $filename,
                    'rm' => false,
                ],
                DirectoryList::VAR_DIR
            );
        } catch (\Throwable $e) {
            return $this->_redirect('massproducts/index/index');
        }
    }
}
