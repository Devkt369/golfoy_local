<?php

namespace ArtM\InvoiceGrid\Controller\Adminhtml\Export;

use Magento\Backend\App\Action;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\App\Filesystem\DirectoryList as AppDirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;

class Fcsv extends Action
{
    
    protected $directoryList;
    protected $fileFactory;

    
    public function __construct(
        Action\Context $context,
        DirectoryList $directoryList,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->directoryList = $directoryList;
        $this->fileFactory   = $fileFactory;
    }

    public function execute()
    {   
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        try{
            $fileName = $this->getRequest()->getParam('id');  
            if($fileName){
                $filePath           = 'invoices/'.$fileName;
                $contentType        = 'application/octet-stream';
                $contentDisposition = 'attachment';
                return $this->fileFactory->create(
                    $fileName,
                    [
                        'type'  => 'filename',
                        'value' => $filePath,
                        'rm'    => false,
                    ],
                    AppDirectoryList::VAR_DIR,
                    $contentType,
                    null,
                    true
                );               
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage("could not find the file.");
            return $resultRedirectFactory->setPath('*/*/download');
        }          
    }
}
