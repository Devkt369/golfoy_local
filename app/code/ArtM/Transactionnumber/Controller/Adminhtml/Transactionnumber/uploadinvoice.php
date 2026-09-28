<?php
namespace ArtM\Transactionnumber\Controller\Adminhtml\Transactionnumber;

use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\Controller\ResultFactory;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Uploadinvoice extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $_pageFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory, //img
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory, //img
        \Magento\Framework\Filesystem $fileSystem //img
    )   
    {
        
        
        
        $this->resultPageFactory = $resultPageFactory;
        $this->fileUploaderFactory = $fileUploaderFactory;
        $this->fileSystem=$fileSystem;
        
        $this->_messageManager = $messageManager;
        
        return parent::__construct($context);
    }
    
    public function createCsrfValidationException(RequestInterface $request): ? InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
    
    public function execute()
    {
//        echo "Order id here";
        //Creating object for redirecting to previous page
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        
        
        
        if( $this->getRequest()->getParam('order_id') != null ){
            
            $data = $this->getRequest()->getPost();
            $invoicepath = array();
            
            $order_id = $this->getRequest()->getParam('order_id');
            $invoicepath = ($this->getRequest()->getFiles('custom_invoice') == '') ? ' ' : $this->getRequest()->getFiles('custom_invoice');
            
            $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $_objectManager->create('Magento\Sales\Model\Order');
            $order->load( $order_id );
            
            $filenames = '';
            $i = 1;
            try{
//                echo "invoicepath count is a: ".count($invoicepath);
                foreach($invoicepath as $image){
                    $uploader = $this->fileUploaderFactory->create(['fileId' => $image ]);
                    $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'pdf']);
                    $uploader->setAllowRenameFiles(true);
                    $uploader->setFilesDispersion(false);
                    $uploader->setAllowCreateFolders(true);
                    
                    $pathtofolder = "/custom_invoices/".time();
                    $mediaUrl=$this->fileSystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA)->getAbsolutePath( $pathtofolder );

                    //final saved name of image 
                    $finalfilename = time()."-".$i.".".$uploader->getFileExtension();
                    
                    $filenames = $filenames.$pathtofolder.'/'.$finalfilename.';-;';
                    $uploader->save( $mediaUrl, $finalfilename);
                    $i++;
                }
//                echo $pathtofolder;
//                echo $filenames;
                
            }
            catch( \Exception $e ){
                $this->_messageManager->addError($e->getMessage());
            }
            
            
            //check if order id exists
            if($order->getId()){
                if( $order->getData('invoicepath') == null ){
                    //echo "here1";
                    $order->setData('invoicepath' , $filenames );
                    if( $order->save() ){
                        //echo "here2";
                        $this->_messageManager->addSuccess(__("Invoice was successfully added"));
                    }
                }else{
                    $this->_messageManager->addError(__("Invoice was already added"));
                }
            }
        }
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }   
}