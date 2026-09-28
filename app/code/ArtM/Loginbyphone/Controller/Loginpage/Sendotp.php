<?php

namespace ArtM\Loginbyphone\Controller\Loginpage;

use Magento\Framework\Controller\ResultFactory;    
use Magento\Framework\App\ObjectManager;
/*
use Magento\Customer\Model\Config\Share as ConfigShare;
use Magento\Store\Model\ScopeInterface;*/

class Sendotp extends \Magento\Framework\App\Action\Action
{   
    public $productCollectionFactory;
    protected $_storeManager;
    protected $_customerCollectionFactory;
    
//    protected $_storeManager;
    protected $filterBuilder;
    protected $searchCriteriaBuilder;
    protected $customeraddressRepository;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \ArtM\Loginbyphone\Model\SendotpFactory $sendotp,
        \ArtM\Loginbyphone\Model\GetkeysFactory $getkeys,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        \ArtM\Loginbyphone\Helper\Data $helper,
        /*
        
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepositoryInterface,

        
        
//        \Magento\Framework\App\Helper\Context $context,
//        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Api\FilterBuilder $filterBuilder,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Customer\Api\AddressRepositoryInterface $customeraddressRepository,*/
        array $data = []
    ) {
        /*
        
        $this->_customerRepositoryInterface = $customerRepositoryInterface;
        
        
        
//        $this->_storeManager = $storeManager;
        $this->filterBuilder = $filterBuilder;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->customeraddressRepository = $customeraddressRepository;*/
        
        $this->helper = $helper;
        $this->_sendotp = $sendotp;
        $this->_getkeys = $getkeys;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        parent::__construct($context);
    }
    
    
    
    
    
    public function execute()
    {
        $proceed = false;
        date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
        $date = date('Y-m-d H:i:s');
        $model = $this->_sendotp->create();
        $getkeys_model = $this->_getkeys->create();
        
        $result = $this->resultJsonFactory->create();
        
        //getting values of all parameters
//        $phone_number_length = 10;
        $phone = ($this->getRequest()->getParam('phone') == '') ? ' ' : $this->getRequest()->getParam('phone');
        $otp = rand(111111,999999);
        $return_html = '';
        $id = '';
        $otp_count = '';
        $custid = '';
        $sent_otp = '';
        
     
        
        
        if( trim( $phone ) != '' ){
            
            
            
            
            
            
            $customerCollection= $this->_customerCollectionFactory->create();
            $customerCollection->addAttribuTeToFilter('phone_number', $phone);

            //if customer is found with this phone
            if($customerCollection->getSize() > 0){
                $customer = $customerCollection->getData();
                $custid = $customer[0]['entity_id'];
                $proceed == true;
            }
            if($proceed == false){
                $custid = $this->helper->getCustomeridwithaddress($phone);
                if($custid == false){
                    $custid = $this->helper->getCustomeridwithaddress($phone,0);
                }
                if($custid == false){
                    $custid = $this->helper->getCustomeridwithaddress($phone,91);
                }
            }
            
            /*
            if( $custid != '' && $custid != false ){
                echo 'in here with id '.$custid;
            }else{
                echo 'not in here';
            }
            exit;*/
            
            if( $custid != '' && $custid != false ){
                
                
                $otpnumbercollection = $this->_sendotp->create()->getCollection()->addFieldToFilter('phonenumber', $phone);
                $products = array();
                foreach($otpnumbercollection as $item){
                    $products[] = $item->getData(); 
                }

                if( count($products) > 0 ){
                    //if number found then delete it and add a new one after this loop
                    foreach($products as $item){                    
                        $id = $item['id'];
                        $model->load( $id );
                        $model->setOtp($otp);
                        $model->setSentAt($date);
                        $model->save();
                        
                        $otp_count = 'multiple';
                        //$return_html .= 'success:OTP was successfully updated to database!.';
//                        $this->messageManager->addSuccess( __('OTP was successfully updated to database!  For now: otp: '.$otp ) );
                        //echo "updated ".$id."<br>";
                    }
                }else{
                    $model->addData([
                        "phonenumber" => $phone,
                        "sent_at" => $date,
                        "otp" => $otp
                    ]);
                    $saveData = $model->save();
                    if($saveData){
                        $otp_count = 'single';
                        //$return_html .= 'success:OTP was successfully added to database!.';
//                        $this->messageManager->addSuccess( __('OTP was successfully added to database!  For now: otp: '.$otp ) );
                    }
                }
                $sent_otp = 'yes';
                
                  
                $getkeys_collection = $getkeys_model->getCollection();
                
                $sms_keys_and_data = array();
                foreach($getkeys_collection as $item){
                    $sms_keys_and_data[] = $item->getData(); 
                }  
                
                if( count($sms_keys_and_data) > 0 ){
                    foreach($sms_keys_and_data as $sms_keys_and_data_object){                    
                        $application_id = $sms_keys_and_data_object['application_id'];
                        $application_token = $sms_keys_and_data_object['application_token'];
                        $unicode = $sms_keys_and_data_object['unicode'];
                        $sender_id = $sms_keys_and_data_object['sender_id'];
                        $sender_id_value = $sms_keys_and_data_object['sender_id_value'];
                        $country = $sms_keys_and_data_object['country'];
                        $url = $sms_keys_and_data_object['url'];       
                    }
                }
                $min_valid_for = 10;
                if( $sent_otp = 'yes' ){
//$otp_text_sms = $otp." is your One-Time-Password(OTP) to login at your Golfoy account. It is valid for  ".$min_valid_for." mins. If this is not you, Please contact  91-9915065500";
                    
                    $otp_text_sms = $otp." is your One-Time-Password(OTP) to login at your Golfoy account. It is valid for  10 mins. If this is not you, Please contact  91-9915065500";
                   /* $otp_text_sms = $otp." is your One-Time-Password(OTP) to login at your Golfoy account. It is valid for  10 mins.
                    If this is not you, Please contact  91-9915065500
                    @golfoy.com #".$otp;*/

                    
                    $url_address = $url;
                    $status = '';
                    $query = "application_id=".$application_id."&application_token=".$application_token."&number=91".$phone."&text=".$otp_text_sms."&unicode=".$unicode."&sender_id=".$sender_id."&sender_id_value=".$sender_id_value."&country=".$country;
                    
                    
                    
                    /*
//                    $res = file_get_contents( $url_address."?".$query);
                    if ( $custid != '' ){
                        $this->messageManager->addSuccess( __("OTP was successfully sent." ) );
//                        $this->messageManager->addSuccess( __("OTP was successfully sent. application_id".$application_id."application_token".$application_token."unicode".$unicode."sender_id".$sender_id."sender_id_value".$sender_id_value."country".$country."url".$url." | res:".$otp_text_sms ." | Customer id:".$custid ) );
                    }else{
                        $this->messageManager->addError( __('Some error occured. please try again after sometime.' ) );
                    }
                    */
                    
                    
                    
                    
                    $res = file_get_contents( $url_address."?".$query);
                    if ( strpos($res, '"status":"accepted"') !== false  ){
                        $return_html .= 'success:OTP was successfully sent.';
                        //$this->messageManager->addSuccess( __("OTP was successfully sent." ) );
//                        $this->messageManager->addSuccess( __("OTP was successfully sent. application_id".$application_id."application_token".$application_token."unicode".$unicode."sender_id".$sender_id."sender_id_value".$sender_id_value."country".$country."url".$url." | res:".$res.$otp_text_sms ." | Customer id:".$custid ) );
                    }else{
                        //$this->messageManager->addError( __('Some error occured. please try again after sometime.' ) );
                    }
                    
                    
                    
                    
                    
                    
                    
                    
                }
            }else{
            //if customer is not found with this phone
                $return_html .= 'error:Customer not found with this Phone Number';
               // $this->messageManager->addError( __('Customer not found with this Phone Number') );
            }
            
            //saving otp here
        }else{
            $return_html .= 'error:Phone is not ten digit or either it is empty';
            
            //$this->messageManager->addError( __('Phone is not ten digit or either it is empty') );
            $sent_otp = 'no';
        }
        $data=Array
        (
            'sent_otp' => $sent_otp,
            'html' => $return_html,
            'id' => $custid,
            'otp_count' => $otp_count,
        );
        
        
        return $result->setData($data);   
         
    }
}
