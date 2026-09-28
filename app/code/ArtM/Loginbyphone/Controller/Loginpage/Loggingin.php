<?php

namespace ArtM\Loginbyphone\Controller\Loginpage;

use Magento\Framework\Controller\ResultFactory;    
use Magento\Framework\App\ObjectManager;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;

class Loggingin extends \Magento\Framework\App\Action\Action
{   
    public $productCollectionFactory;
    protected $_storeManager;
    protected $_customerCollectionFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \ArtM\Loginbyphone\Model\SendotpFactory $sendotp,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $customerCollectionFactory,
        
        CustomerRepositoryInterface $customerRepository,
        CustomerSession $customerSession,
        \ArtM\Loginbyphone\Helper\Data $helper,
        
        array $data = []
    ) {
        $this->helper = $helper;
        $this->_sendotp = $sendotp;
        $this->_customerCollectionFactory = $customerCollectionFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->_storeManager = $storeManager;
        $this->priceCurrency = $priceCurrency;
        $this->productStatus = $productStatus;
        $this->productVisibility = $productVisibility;
        
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        
        parent::__construct($context);
    }
    
    public function execute()
    {
        $model = $this->_sendotp->create();
        
        $result = $this->resultJsonFactory->create();
        
        date_default_timezone_set("Asia/Calcutta");   //India time (GMT+5:30)
        $date = date('Y-m-d H:i:s');
        
        
        //getting values of all parameters
        $phone_number_length = 10;
        $phone = ($this->getRequest()->getParam('phone') == '') ? ' ' : $this->getRequest()->getParam('phone');
        $otp = ($this->getRequest()->getParam('otp') == '') ? ' ' : $this->getRequest()->getParam('otp');
        
        $return_html = '';
        $found_otp = 0;
        
        if( trim( $phone ) != '' && strlen($phone) == $phone_number_length ){
            $proceed = false;
            
            $customerCollection= $this->_customerCollectionFactory->create();
            $customerCollection->addAttribuTeToFilter('phone_number', $phone);

            //if customer is found with this phone
            if($customerCollection->getSize() > 0){
                $customer = $customerCollection->getData();
                $custid = $customer[0]['entity_id'];
            }

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
                
            if( $custid != '' && $custid != false ){
                
                
                $otpnumbercollection = $this->_sendotp->create()
                    ->getCollection()
                    ->addFieldToFilter('phonenumber', $phone)
                    ->addFieldToFilter('otp', $otp);
                
                $otp = array();
                foreach($otpnumbercollection as $item){
                    $otp[] = $item->getData(); 
                }

                if( count($otp) > 0 ){
                    //get diffrence between saved date/time and current date/time
                    $start_date = strtotime( $otp[0]['sent_at'] );
                    $end_date = strtotime( $date );
                    $otp_valid_for = 300; //in seconds (5 minutes)
                    
                    $diff_in_seconds = ($end_date - $start_date)/60/60/24*86400;
                    
                    
                    if( $diff_in_seconds > $otp_valid_for ){
                        $return_html.= "error:OTP Expired";
                        //$this->messageManager->addSuccess( __('OTP Expired') );
                    }else{
                        $found_otp = 1;
                        $return_html.= "success:OTP found";
                        //$this->messageManager->addSuccess( __('OTP found') );

                        $customer = $this->customerRepository->getById($custid);
                        $this->customerSession->setCustomerDataAsLoggedIn($customer);
                    }
                }else{
                    $return_html.= "error:OTP not found";
                    //$this->messageManager->addError( __('OTP not found') );
                }
                
            }else{
            //if customer is not found with this phone
                $return_html .= 'error:Customer not found with this Phone Number';
            }
            
            //saving otp here
        }else{
            $return_html .= 'error:Phone is not ten digit or either it is empty';
        }
        $data=Array
        (
            'html' => $return_html,
            'found_otp' => $found_otp,
        );
        return $result->setData($data);   
         
    }
}
