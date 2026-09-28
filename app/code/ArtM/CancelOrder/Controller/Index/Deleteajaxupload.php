<?php

namespace ArtM\CancelOrder\Controller\Index;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\View\Result\PageFactory;

class Deleteajaxupload extends \Magento\Framework\App\Action\Action
{
    protected $filesystem;
    protected $resultPageFactory;
    protected $resultJsonFactory;

    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Filesystem $filesystem
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;  
        $this->resultJsonFactory = $resultJsonFactory;
        $this->filesystem = $filesystem;
    }
    public function execute(){        
         $page = $this->resultPageFactory->create();
         $json = $this->resultJsonFactory->create();        
         $mediapath =  $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
         $status = 'f';
          $path = $this->getRequest()->getParam('path');
         if(trim($path) != ''){
                if(strpos($path,'return_image') !== false){
                 $fullpath = $mediapath.$path;
                 if(file_exists($fullpath)) {
                     unlink($fullpath);
                     $status = 't';
                 }
             }
         }
         return $json->setData(['status' => $status]);    
     
}
    
}
