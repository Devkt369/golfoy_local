<?php
namespace ArtM\OrderFeedback\Controller\Adminhtml\Response;

use \Magento\Framework\Message\ManagerInterface;
use \Magento\Framework\Controller\ResultFactory;

use \Magento\Framework\App\CsrfAwareActionInterface;
use \Magento\Framework\App\RequestInterface;
use \Magento\Framework\App\Request\InvalidRequestException;

class Save extends \Magento\Framework\App\Action\Action
{
    protected $_pageFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \ArtM\OrderFeedback\Model\MaintableFactory $orderfeedback,
        \Magento\Framework\Message\ManagerInterface $messageManager
    )   
    {
        $this->_messageManager = $messageManager;
        $this->orderfeedback = $orderfeedback;
        
        return parent::__construct($context);
    }
    public function execute(){
        date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        $order_id = ($this->getRequest()->getParam('order_id') == '') ? ' ' : $this->getRequest()->getParam('order_id');
        $feedback_response = ($this->getRequest()->getParam('feedback_response') == '') ? ' ' : $this->getRequest()->getParam('feedback_response');
        
        $orderfeedback_model = $this->orderfeedback->create();
        $orderfeedback_collection = $orderfeedback_model->getCollection()->addFieldToFilter('order_id', $order_id);
        
        if( $orderfeedback_collection->count() != 0 ){
            foreach($orderfeedback_collection as $orderfeedback){
                $orderfeedback_data = $orderfeedback->getData();
            }
            $feedback_id = $orderfeedback_data['id'];
            $feedback_existing_response = $orderfeedback_data['response'];
            
            $date_time = date('Y-m-d H:i:s');
            $feedback_response = $feedback_existing_response."<br>".$date_time." : ".$feedback_response;
            
            $orderfeedback_model->load( $feedback_id );
            $orderfeedback_model->setResponse( $feedback_response );
            $orderfeedback_model->save();
            
            $this->_messageManager->addSuccess(__("Transaction number was successfully added"));
            
            /*
            $orderfeedback_model->setData('response' , $feedback_response );
            if( $orderfeedback_model->save() ){
                $this->_messageManager->addSuccess(__("Transaction number was successfully added"));
            }*/
        }else{
            $this->_messageManager->addError(__("Transaction number was already added"));    
        }
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }
}