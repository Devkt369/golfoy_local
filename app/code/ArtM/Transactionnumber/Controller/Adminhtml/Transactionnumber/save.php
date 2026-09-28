<?php
namespace ArtM\Transactionnumber\Controller\Adminhtml\Transactionnumber;

use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\Controller\ResultFactory;

use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Save extends \Magento\Framework\App\Action\Action implements CsrfAwareActionInterface
{
    protected $_pageFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )   
    {
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
        //Creating object for redirecting to previous page
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        if( $this->getRequest()->getParam('order_id') != null ){
            $order_id = $this->getRequest()->getParam('order_id');
            $transaction_num = $this->getRequest()->getParam('transaction_num');
            //echo $order_id."<br>";
            //echo $transaction_num."<br>";
            //loading order
            $_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $order = $_objectManager ->create('Magento\Sales\Model\Order');
            $order->load( $order_id );
//            print_r( $order->getData() );
            
            //check if order id exists
            //echo $order->getId()."<br>";
            
            if($order->getId()){
                if( $order->getData('paymentoption') == null ){
                    //echo "here1";
                    $order->setData('paymentoption' , $transaction_num );
                    if( $order->save() ){
                        //echo "here2";
                        $this->_messageManager->addSuccess(__("Transaction number was successfully added"));
                    }
                }else{
                    $this->_messageManager->addError(__("Transaction number was already added"));
                }
            }
        }
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }   
}