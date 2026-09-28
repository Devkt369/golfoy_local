<?php

namespace ArtM\CancelOrder\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use ArtM\CancelOrder\Model\CancelOrderCustomFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Action\Action;
use ArtM\CancelOrder\Model\ReturnOrderImage;
use ArtM\CancelOrder\Model\BankAccountDetails;
use Meetanshi\Sms\Helper\Data;

class Submit extends Action
{
    protected $resultPageFactory;
    protected $extensionFactory;
    protected $orderFactory;
    protected $returnimages;
    protected $refundModel;
    protected $_smsHelper;
    protected $commonhelper;
    
    public function __construct(
        Context $context,
        Data $_smsHelper,
        PageFactory $resultPageFactory,
        CancelOrderCustomFactory $extensionFactory,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \ArtM\Common\Helper\Data $commonhelper,
        ReturnOrderImage $returnimages,
        BankAccountDetails $refundModel
    )
    {
        $this->commonhelper = $commonhelper;
        $this->resultPageFactory = $resultPageFactory;
        $this->extensionFactory = $extensionFactory;
        $this->returnimages = $returnimages;
        $this->orderFactory = $orderFactory;
        $this->refundModel = $refundModel;
        $this->_smsHelper = $_smsHelper;
        parent::__construct($context);
    }

    public function execute()
    {      
        $orderId = $this->getRequest()->getParam('order_id');
        $order_id = $this->getRequest()->getParam('order_id');
        $theorderstatus = $this->getRequest()->getParam('order_status');
        
        $model = $this->extensionFactory->create();
        
        $request_type = $this->getRequest()->getParam('return_cancel_status');    

        $insert = $this->returnimages; 
                
        if($request_type == 'cancel' ){
        
            $order_id = $this->getRequest()->getParam('order_id');
            $order_state = $this->getRequest()->getParam('order_state');
            $order_status = $this->getRequest()->getParam('order_status');
            $customer_id = $this->getRequest()->getParam('customer_id');
            
            $customer_name = ($this->getRequest()->getParam('customer_name') == '') ? ' ' : $this->getRequest()->getParam('customer_name');
            $customer_email = ($this->getRequest()->getParam('customer_email') == '') ? ' ' : $this->getRequest()->getParam('customer_email');
            $customer_phone = ($this->getRequest()->getParam('customer_phone') == '') ? ' ' : $this->getRequest()->getParam('customer_phone');
            $incremented_id = ($this->getRequest()->getParam('incremented_id') == '') ? ' ' : $this->getRequest()->getParam('incremented_id');
            
            
            $order = $this->orderFactory->create()->load($orderId);
            
            $state = $order->getState();
            $orderstatus = $order->getStatusLabel();
            $theorderstatus = $order->getStatus();
            $newstate='';
            if ( $state == 'pending' || $state == 'new' ||$theorderstatus=='processing'){
                $status = 'cancelpending';
                $newstate = 'processing';
            
                $comment = '';
                $isNotified = false;
                $order->setState($newstate);
                $order->setStatus($status);
              //  $order->addStatusToHistory( $order->getStatus(), $comment );
                $order->save();

                $model->addData([
                    "order_id" => $order_id,
                    "order_state" => $order_state,
                    "order_status" => $order_status,
                    "return_cancel_status" => $request_type,
                    "customer_id" => $customer_id
                ]);

                $saveData = $model->save();
                if($saveData){

                    $data = [
                        'orderId'   =>  $incremented_id,
                        'firstname' => $customer_name,
                        'sku'       => 'N/A'
                    ];
                    $observerName = 'order_cancelation_requested';

                    $this->_smsHelper->sendSmsCancelRequest($data , $customer_phone , $observerName);

                    $this->messageManager->addSuccess( __('Order cancellation request was sent successfully, We shall contact you shortly') );
                    $reciept_name = 'Mohit';
                    $reciept_email = 'mohit@mage.golfoy.com';
                    
                    
                    if( $customer_name != '' || $customer_email != '' ){
                        $this->commonhelper->send_mail('cancelorder', $customer_name, $customer_email, $order_id);
                    }
                }
            }
            // return $this->_redirect('sales/order/history');
             
        }else if( $request_type == 'return' || $request_type == 'replace' ){
            
            
            
            $order_id = ($this->getRequest()->getParam('order_id') == '') ? ' ' : $this->getRequest()->getParam('order_id');
            $incremented_id = ($this->getRequest()->getParam('incremented_id') == '') ? ' ' : $this->getRequest()->getParam('incremented_id');
            $order_state = ($this->getRequest()->getParam('order_state') == '') ? ' ' : $this->getRequest()->getParam('order_state');
            $order_status = ($this->getRequest()->getParam('order_status') == '') ? ' ' : $this->getRequest()->getParam('order_status');
            $customer_id = ($this->getRequest()->getParam('customer_id') == '') ? ' ' : $this->getRequest()->getParam('customer_id');
            $return_reason = ($this->getRequest()->getParam('return_reason') == '') ? ' ' : $this->getRequest()->getParam('return_reason');
            
            $product_ids = ($this->getRequest()->getParam('product_ids') == '') ? ' ' : $this->getRequest()->getParam('product_ids');
            $customer_name = ($this->getRequest()->getParam('customer_name') == '') ? ' ' : $this->getRequest()->getParam('customer_name');
            $customer_email = ($this->getRequest()->getParam('customer_email') == '') ? ' ' : $this->getRequest()->getParam('customer_email');
            $customer_phone = ($this->getRequest()->getParam('customer_phone') == '') ? ' ' : $this->getRequest()->getParam('customer_phone');
            $product_name = ($this->getRequest()->getParam('product_name') == '') ? ' ' : $this->getRequest()->getParam('product_name');
            

            if($request_type == 'return'){
                $data = [
                    'orderId'       => $incremented_id,
                    'firstname'     => $customer_name,
                    'product_name'  => $product_name
                ];
                $observerName = 'order_return_requested';
                $this->_smsHelper->sendSmsReturnRequest($data , $customer_phone , $observerName);
            }else{
                $data = [
                    'orderId'       => $incremented_id,
                    'firstname'     => $customer_name,
                    'product_name'  => $product_name
                ];
                $observerName = 'order_replace_requested';
                $this->_smsHelper->sendSmsReplaceRequest($data , $customer_phone , $observerName);
            }  

            // Return image insert into table

            $filesname = ($this->getRequest()->getParam('review_media_files') == '') ? ' ' : $this->getRequest()->getParam('review_media_files');          
            if (is_array($filesname)){
                $string = implode(',', $filesname);
            }else{
                $string = $filesname;

            }
            $firstreson = ($this->getRequest()->getParam('firstreson') == '') ? '' : $this->getRequest()->getParam('firstreson');
            $subreson = ($this->getRequest()->getParam('subreson') == '') ? '' : $this->getRequest()->getParam('subreson');
            
            $firstreason = ["0"=>"Size/color Issue",
                            "1"=>"Did not like the Product",
                            "2"=>"Defective/Damage Product received",
                            "3"=>"Wrong item Shipped",
                            "show"=>"Other"];
                    
                    $subreason = ["0"=>"Wrong Color Delivered",
                                  "1"=>"Different from what was ordered",
                                  "2"=>"Delivered wrong size",
                                  "3"=>"Product not as displayed on website"
                    ];

            if ($firstreson == "show"){
                $comnt = $firstreason[$firstreson].' | '.$return_reason;
            }else if($firstreson == "0") {
                $comnt = $firstreason[$firstreson] .' | '. $subreason[$subreson];
            }else{
                $comnt = $firstreason[$firstreson];
            }

            
        //   end for return image insert into table

            $order = $this->orderFactory->create()->load($orderId);
            
            $state = $order->getState();
            $orderstatus = $order->getStatusLabel();
            $theorderstatus = $order->getStatus();
            
            if ( $state == 'complete' ){
               //$status = 'return';
               // $state = 'complete';
               $state = 'complete';
               }
            $status = 'replacement_order';
            
            $comment = '';
            $isNotified = false;
            $order->setStatus($status);
            $order->setState($state);
            $order->addStatusToHistory( $order->getStatus(), $comment );
            $order->addStatusToHistory( $order->getStatus(), "Replacement Request || $comnt" );
            $order->save();


            // insert refund details
            $refundaccountid = 0; 
            $method = $this->getRequest()->getParam('refund_type'); 
           
            if($method == 'refund_bank'){
                $refundaccountid = $this->getRequest()->getParam('bankrefund'); 
                if($refundaccountid == 0){                                 
                    $accnumber =  $this->getRequest()->getParam('refund_bank_accnumber'); 
                    $ifsc =  $this->getRequest()->getParam('refund_bank_ifsccode'); 
                    $bnkname =  $this->getRequest()->getParam('refund_bank_name'); 
                    $bnkbranch =  $this->getRequest()->getParam('refund_bank_branch'); 
                    
                    $racollection = $this->refundModel->getCollection();
                    $racollection->addFieldToFilter('details', ['like' => '%' . $accnumber.'||' . '%'])
                    ->addFieldToFilter('customer_id', ['eq' => $customer_id]);
                    $numRecords = $racollection->getSize();
                    if ($numRecords > 0) {
                       $record = $racollection->getFirstItem();
                       $refundaccountid = $record->getId();
                    }else{ 
                        $this->refundModel->setData([
                            "customer_id" => $customer_id,
                            "method" => $method,
                            "details" => $accnumber.'||'.$ifsc.'||'.$bnkname.'||'.$bnkbranch
                        ]);
            
                        $this->refundModel->save();
                        $refundaccountid = $this->refundModel->getId(); 
                   }
                }
            } else if ($method == 'refund_upi') {
                $upiAccountId = (int)$this->getRequest()->getParam('upi_refund_account_id');
                
                if ($upiAccountId > 0) {
                     $refundaccountid = $upiAccountId;
                } else {
                    $upiId =  $this->getRequest()->getParam('refund_upi_id');
                    
                    $racollection = $this->refundModel->getCollection();
                    $racollection->addFieldToFilter('details', ['eq' => $upiId])
                        ->addFieldToFilter('method', ['eq' => 'refund_upi'])
                        ->addFieldToFilter('customer_id', ['eq' => $customer_id]);
                        
                    $numRecords = $racollection->getSize();
                    if ($numRecords > 0) {
                        $record = $racollection->getFirstItem();
                        $refundaccountid = $record->getId();
                    } else {
                        $this->refundModel->setData([
                            "customer_id" => $customer_id,
                            "method" => $method,
                            "details" => $upiId
                        ]);
    
                        $this->refundModel->save();
                        $refundaccountid = $this->refundModel->getId();
                    }
                }
            }
            
            date_default_timezone_set("UTC");  
            $currentdate = date('Y-m-d H:i:s' );
            
            $model->addData([
                "order_id" => $order_id,
                "order_state" => $order_state,
                "order_status" => $order_status,
                "return_cancel_status" => $request_type,
                "customer_id" => $customer_id,
                "return_reason" => $comnt,
                "product_ids" => $product_ids,
                "customer_name" => $customer_name,
                "customer_email" => $customer_email,
                "customer_phone" => $customer_phone,
                "refund_type" => $method,
                "current_status" => $request_type." requested",
                "created_at" => $currentdate,
                "refund_account_id" => (int) $refundaccountid
            ]);

            $insert->setData([
                "order_id" => $order_id,
                "cancel_product_img" => $string,
                "prd_id" => $product_ids,
                "return_cmnt" => $comnt
            ]);
            
            $insertimage = $insert->save();
            $saveData = $model->save();
            if($saveData){
                $this->messageManager->addSuccess( __('Order return/replace request was sent successfully, We shall contact you shortly') );
                
                if( $customer_name != '' || $customer_email != '' ){
                    $this->commonhelper->send_mail('return_replace', $customer_name, $customer_email, $order_id );
                }
                
            }
        }
            $url = '/sales/order/view/order_id/'.$order_id.'';
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($url);
        return $resultRedirect;
     
      
        
    }
}