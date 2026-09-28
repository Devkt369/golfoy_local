<?php

namespace Meetanshi\Sms\Helper;

use Magento\Eav\Model\Entity\Attribute\Backend\JsonEncoded;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Meetanshi\Sms\Model\SMSGatewayHistoryFactory;
use Meetanshi\Sms\Model\SMSQueueFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Class Data
 * @package Meetanshi\Sms\Helper
 */
class Data extends AbstractHelper
{
    /**
     *
     */
    const SMS_ENABLE = 'sms/general/enabled';
    
    /**
     *
     */
    const SMS_APIPROVIDER = 'sms/apicredential/apiprovider';
    /**
     *
     */
    const SMS_APIKEY = 'sms/apicredential/apikey';
    const SMS_APIKEY_FOR_OTHER = 'sms/apicredential/apikeyforall';
    /**
     *
     */
    const SMS_SENDERID = 'sms/apicredential/senderid';
    /**
     *
     */
    const SMS_APIURL = 'sms/apicredential/apiurl';
    /**
     *
     */
    const SMS_MSGTYPE = 'sms/apicredential/msgtype';
    /**
     *
     */
    const SMS_URL = 'sms/apicredential/customurl';

    /**
     *
     */
    const TWILLIOSID = 'sms/apicredential/twiliosid';
    /**
     *
     */
    const TWILLIOTOKEN = 'sms/apicredential/twiliotoken';
    /**
     *
     */
    const TWILLIONUMBER = 'sms/apicredential/twilionumber';


    const SMS_OTP_ENABLE = 'sms/loginotp/enabled';
    const SMS_OTP_SMSTEXT = 'sms/loginotp/smstext';
    
    const SMS_CANCEL_ENABLE = 'sms/cancelreturnreplace/enabledcancel';
    const SMS_CANCELREQUEST_TEXT = 'sms/cancelreturnreplace/cancelsmstext';
    const SMS_RETURN_ENABLE = 'sms/cancelreturnreplace/enabledreturn';
    const SMS_RETURNSMSTEXT_ENABLE = 'sms/cancelreturnreplace/returnsmstext';
    const SMS_REPLACE_ENABLE = 'sms/cancelreturnreplace/enabledreplace';
    const SMS_CANCELAPPROVED_ENABLE = 'sms/cancelreturnreplace/enabledcancelapproved';
    const SMS_CANCELAPPROVEDTEXT_ENABLE = 'sms/cancelreturnreplace/approvedcancelsmstext';
    const SMS_RETURNAPPROVED_ENABLE = 'sms/cancelreturnreplace/enabledreturnapproved';
    const SMS_REPLACEAPPROVED_ENABLE = 'sms/cancelreturnreplace/enabledreplaceapproved';
    const SMS_REPLACEAPPROVEDTEXT_ENABLE = 'sms/cancelreturnreplace/approvedreturnsmstext';
    const SMS_REPLACESMSTEXT_ENABLE = 'sms/cancelreturnreplace/replacesmstext';
    const SMS_REPLACEAPPROVEDSMSTEXT_ENABLE = 'sms/cancelreturnreplace/approvedreplacesmstext';
    const SMS_REWARDTRANSACTION_ENABLE = 'sms/rewardtransaction/enabledreward';
    const SMS_REWARDTRANSACTION_TEXT = 'sms/rewardtransaction/rewardtransactionsmstext';

    const SMS_REWARDTEXPIRY_ENABLE = 'sms/rewardsexpiry/enabledreward';
    const SMS_REWARDEXPIRY_TEXT = 'sms/rewardsexpiry/rewardexpirysmstext';

    const SMS_GIFTCARDEXPIRY_ENABLE = 'sms/giftcardexpiry/enabledgiftcard';
    const SMS_GIFTCARDEXPIRY_TEXT = 'sms/giftcardexpiry/giftcardexpirysmstext';
    const SMS_GIFTCARDUPDATE_ENABLE = 'sms/giftcardupdates/enabledgiftcardupdate';
    const SMS_GIFTCARDUPDATE_TEXT = 'sms/giftcardupdates/giftcardexpirysmstext';

    const SMS_GIFTCARD_PURCHAGE_ENABLE = 'sms/rewardtransaction/enabledgiftcard';
    const SMS_GIFTCARD_PURCHAGE_TEXT = 'sms/rewardtransaction/giftcardsmstext';

    const SMS_GIFTCARD_PURCHASE_STATUS = 'sms/giftcardpurchage/enabledgiftcardpurchage';
    const SMS_GIFTCARD_PURCHASE_TEMPLATEID = 'sms/giftcardpurchage/templateid_msg91';
    const SMS_GIFTCARD_PURCHASE_PARAMETER = 'sms/giftcardpurchage/parameter_msg91';

    const SMS_QUERYSMSSEND_ADMIN_STATUS = 'sms/querysmssend/enabled';
    const SMS_QUERYSMSSEND_ADMIN_TEXT = 'sms/querysmssend/adminsmstext';
    const SMS_QUERYSMSSEND_CUSTOMER_STATUS = 'sms/querysmssend/customerquerysms';
    const SMS_QUERYSMSSEND_CUSTOMER_TEXT = 'sms/querysmssend/smstextcustomer';

    // SMS Scheduler constants
    const SMS_SCHEDULER_ENABLE = 'sms/smsscheduler/enabled';
    const SMS_SCHEDULER_RESTRICT_FROM_TIME = 'sms/smsscheduler/restrict_from_time';
    const SMS_SCHEDULER_RESTRICT_TO_TIME = 'sms/smsscheduler/restrict_to_time';
    const SMS_SCHEDULER_TIMEZONE = 'sms/smsscheduler/timezone';
    const SMS_SCHEDULER_SEND_TIME = 'sms/smsscheduler/send_time';
    const SMS_SCHEDULER_MAX_RETRY = 'sms/smsscheduler/max_retry_attempts';

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Model smshistory
     *
     * @var $SMSGatewayHistoryFactory 
     */
    protected $SMSGatewayHistoryFactory;

    /**
     * @var SMSQueueFactory
     */
    protected $smsQueueFactory;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * Data constructor.
     * @param Context $context
     * @param ScopeConfigInterface $scopeConfig
     * @param SMSGatewayHistoryFactory $SMSGatewayHistoryFactory
     * @param SMSQueueFactory $smsQueueFactory
     * @param DateTime $dateTime
     * @param TimezoneInterface $timezone
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        SMSGatewayHistoryFactory $SMSGatewayHistoryFactory,
        SMSQueueFactory $smsQueueFactory,
        DateTime $dateTime,
        TimezoneInterface $timezone,
        StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
        $this->SMSGatewayHistoryFactory = $SMSGatewayHistoryFactory;
        $this->smsQueueFactory = $smsQueueFactory;
        $this->dateTime = $dateTime;
        $this->timezone = $timezone;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    public function smsEnabledGiftCardPurchased(){
        return $this->scopeConfig->getValue(self::SMS_GIFTCARD_PURCHASE_STATUS, ScopeInterface::SCOPE_STORE);
    }

    public function sendSmsGiftCardPurchased($data, $mobilenumber, $observerName){
        if ($this->smsEnabledGiftCardPurchased()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            $templateId = $this->scopeConfig->getValue(self::SMS_GIFTCARD_PURCHASE_TEMPLATEID, $storeScope);
            $parametersString = $this->scopeConfig->getValue(self::SMS_GIFTCARD_PURCHASE_PARAMETER, $storeScope);
            $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope);

            $paramKeys = array_map('trim', explode(',', $parametersString));
            $recipient = ['mobiles' => '91' . $mobilenumber];
            foreach ($paramKeys as $key) {
                $recipient[$key] = isset($data[$key]) ? $data[$key] : '';
            }
            $payload = [
                'template_id' => $templateId,
                'short_url' => $shortUrl ?: 0,
                'recipients' => [$recipient]
            ];
            return $this->CheckRestrictedTime($payload, $mobilenumber, $observerName);
        }
    }

    public function smsEnableRewardTransaction(){
        return $this->scopeConfig->getValue(self::SMS_REWARDTRANSACTION_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Send sms on reward point updates
     *
     * @param array $data
     * @param int $mobilenumber
     * @param string $observerName
     * @return string
     */
    public function sendSmsRewardTransaction($data, $mobilenumber, $observerName)
    {
        if ($this->smsEnableRewardTransaction()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            // Get template ID and parameters from Magento config
            $templateId = $this->scopeConfig->getValue('sms/rewardtransaction/templateid_msg91', $storeScope);
            $parametersString = $this->scopeConfig->getValue('sms/rewardtransaction/parameter_msg91', $storeScope);
            $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope); // Optional

            if ($parametersString === null) {
                $this->customLog("SMS Reward Transaction: Parameter list is empty.");
                return;
            }

            $paramKeys = array_map('trim', explode(',', $parametersString));
            $recipient = ['mobiles' => '91' . $mobilenumber];
            foreach ($paramKeys as $key) {
                $recipient[$key] = isset($data[$key]) ? $data[$key] : '';
            }
            $payload = [
                'template_id' => $templateId,
                'short_url' => $shortUrl ?: 0,
                'recipients' => [$recipient]
            ];

            return $this->CheckRestrictedTime($payload, $mobilenumber, $observerName);
        } else {
            $this->customLog("SMS Reward Transaction: Disabled from admin config.");
        }
    }

    public function isSendGiftCardUpdateSms(){
        return $this->scopeConfig->getValue(self::SMS_GIFTCARDUPDATE_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    public function sendGiftCardUpdateSms($phoneNumber, $amount, $giftcardcode, $datetime, $balanceamount) : void {
        if($this->isSendGiftCardUpdateSms()){
            $storescope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $data = array($amount, $giftcardcode, $datetime, $balanceamount);
            
            $templateid = $this->scopeConfig->getValue('sms/giftcardupdates/templateid_msg91', $storescope);
            $parametersstring = $this->scopeConfig->getValue('sms/giftcardupdates/parameter_msg91', $storescope);
            $shorturl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storescope);
            $paramkeys = array_filter(array_map('trim', explode(',', $parametersstring ?? '')));
                $recipient = ['mobiles' => '91' . $phoneNumber];
                foreach ($paramkeys as $index => $key) {
                    $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                }
            $payload = [
                'template_id' => $templateid,
                'short_url' => $shorturl ?: 0,
                'recipients' => [$recipient]
            ];

            $observername = 'gift-card-update-sms';            
            $this->CheckRestrictedTime($payload, $phoneNumber, $observername);
        }
    }

    public function isSendGiftCardExpirySms(){
        return $this->scopeConfig->getValue(self::SMS_GIFTCARDEXPIRY_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    public function sendGiftCardExpirySms($phoneNumber , $validity, $amount, $giftcardcode){

        if($this->isSendGiftCardExpirySms()){

            $data = array($giftcardcode, $amount, $validity);
            $storescope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            $templateid = $this->scopeConfig->getValue('sms/giftcardexpiry/templateid_msg91', $storescope);
            $parametersstring = $this->scopeConfig->getValue('sms/giftcardexpiry/parameter_msg91', $storescope);
            $shorturl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storescope);
            $paramkeys = array_filter(array_map('trim', explode(',', $parametersstring ?? '')));
                $recipient = ['mobiles' => '91' . $phoneNumber];
                foreach ($paramkeys as $index => $key) {
                    $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                }
            $payload = [
                'template_id' => $templateid,
                'short_url' => $shorturl ?: 0,
                'recipients' => [$recipient]
            ];

            $observername = 'gift-card-expiry-sms';

            $this->apicall($payload, $phoneNumber, $observername);
        }
    }

    public function isSendRewardExpirySms(){
        return $this->scopeConfig->getValue(self::SMS_REWARDTEXPIRY_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param string $phoneNumber
     * @param string $expiryDate
     * @param string $expiryPoints
     * @return void
     */
    public function sendSmsRewardExpiration($phoneNumber , $expiryDate, $expiryPoints){
        
        if($this->isSendRewardExpirySms()){

            $data = array($expiryDate, $expiryPoints);
            $storescope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

            $templateid = $this->scopeConfig->getValue('sms/rewardsexpiry/templateid_msg91', $storescope);
            $parametersstring = $this->scopeConfig->getValue('sms/rewardsexpiry/parameter_msg91', $storescope);
            $shorturl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storescope);
            $paramkeys = array_filter(array_map('trim', explode(',', $parametersstring ?? '')));
                $recipient = ['mobiles' => '91' . $phoneNumber];
                foreach ($paramkeys as $index => $key) {
                    $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                }
            $payload = [
                'template_id' => $templateid,
                'short_url' => $shorturl ?: 0,
                'recipients' => [$recipient]
            ];
            $observername = "reward-expiration-alert-send";
            $this->apicall($payload, $phoneNumber, $observername);
        }

    }

    /**
     * @return mixed
     */
    
    public function sendOTPText($otp, $mobilenumber)
    {
        $data = array($otp , 10);

        if ($this->smsEnable()) {
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $enableOTP = $this->scopeConfig->getValue(self::SMS_OTP_ENABLE, $storeScope);

            if ($enableOTP) {
                $templateId = $this->scopeConfig->getValue('sms/loginotp/templateid_msg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/loginotp/parameter_msg91', $storeScope) ?? '';
                $shortUrl = $this->scopeConfig->getValue('sms/msg91/short_url', $storeScope) ?: 0;

                // Clean up parameters
                $paramKeys = array_filter(array_map('trim', explode(',', $parametersString)));

                // Prepare recipient array
                $recipient = ['mobiles' => '91' . $mobilenumber];

                // Fill only available parameter keys with corresponding values from $data
                foreach ($paramKeys as $index => $key) {
                    $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                }

                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl,
                    'recipients' => [$recipient]
                ];

                $observer = 'send_login_otp';
                
                return $this->apiCall($payload, $mobilenumber, $observer);
            }
        }
    }


    public function smsEnable()
    {
        return $this->scopeConfig->getValue(self::SMS_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    public function smsEnabledReplace()
    {
        return $this->scopeConfig->getValue(self::SMS_REPLACE_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param [array] $data
     * @param [int] $mobilenumber
     * @return response
     */
    public function sendSmsReplaceRequest($FetchedData, $mobilenumber, $observerName)
    {        
        $data = array($FetchedData['orderId'] , $FetchedData['firstname'] , $FetchedData['product_name'] );
        try {
            if ($this->smsEnabledReplace()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Get template config
                $templateId = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreplacetemplateidmsg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreplaceparametermsg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope); // Optional

                // Convert parameter list to array
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Build recipient data
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Final payload
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                return $this->CheckRestrictedTime($payload, $mobilenumber, $observerName);
            }
        } catch (\Exception $e) {
            // $this->logger->info($e->getMessage());
            return false;
        }
    }




    public function smsEnabledQueryStatus()
    {
        return $this->scopeConfig->getValue(self::SMS_QUERYSMSSEND_ADMIN_STATUS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @param int $mobilenumbers
     * @return response
     */
    public function sendSmsQuery($data, $mobilenumber, $observer = null)
    {
        try {
            if ($this->smsEnabledQueryStatus()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Get template details from admin config
                $templateId = $this->scopeConfig->getValue('sms/msg91/query_template_id', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/msg91/query_template_parameters', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/msg91/short_url', $storeScope); // Optional

                // Parse parameter keys
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Build recipient data dynamically
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Final payload for MSG91
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                // Call API
                return $this->CheckRestrictedTime($payload, $mobilenumber, $observer);
            }
        } catch (\Exception $e) {
            // $this->logger->info($e->getMessage());
            return false;
        }
    }


    public function smsEnabledUserQueryStatus()
    {
        return $this->scopeConfig->getValue(self::SMS_QUERYSMSSEND_CUSTOMER_STATUS, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @param int $mobilenumbers
     * @return response
     */
    public function sendSmsUserQuery($data, $mobilenumber, $observer = null)
    {
        
                $smsData['name'] = $name;
                $smsData['phonenumber'] = $phoneNumber;
                $smsData['query'] = $query;
                $smsData['username'] = $customer_name;
                $smsData['customer_email'] = $customer_email;
                $smsData['querytype'] = $queryType;
                $smsData['email'] = $email;
        
        
        try {
            if ($this->smsEnabledUserQueryStatus()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Get template and parameters from admin config
                $templateId = $this->scopeConfig->getValue('sms/msg91/query_user_template_id', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/msg91/query_user_template_parameters', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/msg91/short_url', $storeScope); // Optional

                // Convert comma-separated parameters into array
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Dynamically build recipient payload
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Create final payload
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                // Call the API
                return $this->CheckRestrictedTime($payload, $mobilenumber, $observer);
            }
        } catch (\Exception $e) {
            // $this->logger->info($e->getMessage());
            return false;
        }
    }


    public function smsEnabledReplaceApproved()
    {
        return $this->scopeConfig->getValue(self::SMS_REPLACEAPPROVED_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @param int $mobilenumbers
     * @return response
     */
    public function sendSmsReplaceApproved($FetchedData, $mobilenumber, $observer)
    {
        $data = array($FetchedData['orderId'] );       
        
        try {
            if ($this->smsEnabledReplaceApproved()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Get template ID and parameters from admin
                $templateId = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreplaceapprovedtemplateid_msg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreplaceapprovedparameter_msg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope); // optional

                // Convert comma-separated string to array
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Build dynamic recipient payload
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Final payload structure
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                // Send via API
                return $this->CheckRestrictedTime($payload, $mobilenumber, $observer);
            }
        } catch (\Exception $e) {
            // $this->logger->info($e->getMessage());
            return false;
        }
    }
    
    public function trackingnumberadded($orderid, $ordertotal, $tracknumber, $carriername, $mobilenumber, $observername){

        $data = array($orderid , $carriername, $tracknumber);
        $storescope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $smsenabledtrackingadded = $this->scopeConfig->getValue('sms/shipmenttracksaveafter/enabled', $storescope);

        if ( $smsenabledtrackingadded ) {
            $templateid = $this->scopeConfig->getValue('sms/shipmenttracksaveafter/templateid_msg91', $storescope);
            $parametersstring = $this->scopeConfig->getValue('sms/shipmenttracksaveafter/parameter_msg91', $storescope);
            $shorturl = $this->scopeConfig->getValue('sms/msg91/short_url', $storescope);
            $paramkeys = array_filter(array_map('trim', explode(',', $parametersstring ?? '')));
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramkeys as $index => $key) {
                    $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                }
            $payload = [
                'template_id' => $templateid,
                'short_url' => $shorturl ?: 0,
                'recipients' => [$recipient]
            ];
            $this->CheckRestrictedTime($payload, $mobilenumber, $observername);
        }else{
            $this->customlog("trackingnumberadded called, but disable from admin");
        }
    }

    

    public function trackingNumAdded($param1, $param2 = null, $param3 = null, $param4 = null, $param5 = null, $param6 = null)
    {
        //echo "called trackingNumAdded - handling parameters";
        
        // Check if this is the old format (3 parameters) or new format (6 parameters)
        if (func_num_args() == 3) {
            // Old format: trackingNumAdded($FetchedData, $mobilenumber, $observer)
            //echo " - old format with 3 params";
            $FetchedData = $param1;
            $mobilenumber = $param2;
            $observer = $param3;
            
            // Handle as replacement approved SMS (original functionality)
            $data = array($FetchedData['orderId'] ?? '');
            
            try {
                if ($this->smsEnabledReplaceApproved()) {
                    $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                    // Get template ID and parameters from admin
                    $templateId = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreplaceapprovedtemplateid_msg91', $storeScope);
                    $parametersString = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreplaceapprovedparameter_msg91', $storeScope);
                    $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope); // optional

                    // Convert comma-separated string to array
                    $paramKeys = array_map('trim', explode(',', $parametersString));

                    // Build dynamic recipient payload
                    $recipient = ['mobiles' => '91' . $mobilenumber];
                    foreach ($paramKeys as $index => $key) {
                        $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                    }

                    // Final payload structure
                    $payload = [
                        'template_id' => $templateId,
                        'short_url' => $shortUrl ?: 0,
                        'recipients' => [$recipient]
                    ];

                    // Use time restriction check instead of direct apiCall
                    return $this->CheckRestrictedTime($payload, $mobilenumber, $observer);
                }
            } catch (\Exception $e) {
                return false;
            }
        } else {
            // New format: trackingNumAdded($orderid, $ordertotal, $tracknumber, $carriername, $mobilenumber, $observername)
            //echo " - new format with 6 params - redirecting to trackingnumberadded";
            return $this->trackingnumberadded($param1, $param2, $param3, $param4, $param5, $param6);
        }
    }

      
   public function OrderSuccess($FetchedData, $mobilenumber, $observerName)
    {
        $data = array($FetchedData['orderId'] , $FetchedData["order_total"], $FetchedData["first_name"]);

        try {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Get template ID and parameters from admin config
                $templateId = $this->scopeConfig->getValue('sms/order/templateid_msg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/order/parameter_msg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/msg91/short_url', $storeScope);

                // Convert parameter list to array
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Build recipients payload
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                    $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
                }

                // Create final payload
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                // Send SMS using apiCall
                return $this->apiCall($payload, $mobilenumber, $observerName);
        } catch (\Exception $e) {
            return false;
        }
    }
    
    



    public function smsEnabledCancel()
    {
        return $this->scopeConfig->getValue(self::SMS_CANCEL_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param array $data
     * @param int $mobilenumbers
     * @return response
     */
    public function sendSmsCancelRequest($FetchedData, $mobilenumbers, $observerName)
    {
        $data = array($FetchedData['orderId'] );
        
        try {
            if ($this->smsEnabledCancel()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Get template config from admin
                $templateId = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledcanceltemplateidmsg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledcancelparametermsg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope); // optional

                // Prepare parameter keys from comma-separated string
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Build recipients array dynamically
                $recipient = ['mobiles' => '91' . $mobilenumbers];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Final payload
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                // Call API
                return $this->CheckRestrictedTime($payload, $mobilenumbers, $observerName);
            }
        } catch (\Exception $e) {
            // $this->logger->info($e->getMessage());
            return false;
        }
    }


    
    public function smsEnabledCancelApproved()
    {
        return $this->scopeConfig->getValue(self::SMS_CANCELAPPROVED_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param [type] $data
     * @param [type] $mobilenumber
     * @return void
     */
    public function sendSmsCancelApproved($data, $mobilenumber)
    {
        try {
            if ($this->smsEnabledCancelApproved()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Fetch template ID and parameters from admin config
                $templateId = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledcanceltemplateidmsg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledcancelparametermsg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope);

                // Extract and clean parameter keys
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Prepare recipient data
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Build final payload
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                return $this->CheckRestrictedTime($payload, $mobilenumber, 'send_cancel_approved');
            }
        } catch (\Exception $e) {
            // $this->logger->info($e->getMessage());
            return false;
        }
    }




    public function smsEnabledReturn()
    {
        return $this->scopeConfig->getValue(self::SMS_RETURN_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    /**
     * Undocumented function
     *
     * @param arrya $data
     * @param int $mobilenumbers
     * @return response
     */
    public function sendSmsReturnRequest($FetchedData, $mobilenumber, $observerName)
    {
        $data = array($FetchedData['orderId'] );

        try {
            if ($this->smsEnabledReturn()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Fetch template ID and parameters from admin config
                $templateId = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreturntemplateidmsg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreturnparametermsg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/msg91/short_url', $storeScope);

                // Clean and split parameter string
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Prepare recipient payload
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Final payload
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                // Send SMS via apiCall
                return $this->CheckRestrictedTime($payload, $mobilenumber, $observerName);
            }
        } catch (\Exception $e) {
            // $this->logger->info($e->getMessage());
            return false;
        }
    }



    public function smsEnabledReturnApproved()
    {
        return $this->scopeConfig->getValue(self::SMS_RETURNAPPROVED_ENABLE, ScopeInterface::SCOPE_STORE);
    }

   public function sendSmsReturnApproved($FetchedData, $mobilenumber, $observerName)
    {

        $data = array($FetchedData['orderId'] );

        try {
            if ($this->smsEnabledReturnApproved()) {
                $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;

                // Get template ID and parameters from admin config
                $templateId = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreturnapprovedtemplateidmsg91', $storeScope);
                $parametersString = $this->scopeConfig->getValue('sms/cancelreturnreplace/enabledreturnapprovedparametermsg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/msg91/short_url', $storeScope);

                // Convert parameter list to array
                $paramKeys = array_map('trim', explode(',', $parametersString));

                // Build recipients payload
                $recipient = ['mobiles' => '91' . $mobilenumber];
                foreach ($paramKeys as $index => $key) {
                $recipient[$key] = isset($data[$index]) ? $data[$index] : '';
            }

                // Create final payload
                $payload = [
                    'template_id' => $templateId,
                    'short_url' => $shortUrl ?: 0,
                    'recipients' => [$recipient]
                ];

                // Send SMS using apiCall
                return $this->CheckRestrictedTime($payload, $mobilenumber, $observerName);
            }
        } catch (\Exception $e) {
            return false;
        }
    }


    /**
     * @param $storeId
     * @return mixed
     */
    public function getStoreURL($storeId)
    {
        return $this->scopeConfig->getValue('web/secure/base_url', ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * @param $msg
     * @param $mobilenumber
     * @param null $tmpid
     * @param int $testing
     * @param int $otp
     * @return string
     */

    /**
     * Check if SMS should be sent immediately or queued based on time restrictions
     *
     * @param array $payload
     * @param string $mobilenumber
     * @param string|null $observerName
     * @param int $testing
     * @param int $otp
     * @return mixed
     */
    public function CheckRestrictedTime($payload, $mobilenumber, $observerName = null, $testing = 0, $otp = 0)
    {
        //echo "<br>called CheckRestrictedTime";
        // Check if SMS scheduler is enabled
        $schedulerEnabled = $this->scopeConfig->getValue(
            self::SMS_SCHEDULER_ENABLE, 
            ScopeInterface::SCOPE_STORE
        );

        //echo "<br>Scheduler Enabled: " . ($schedulerEnabled ? 'Yes' : 'No');

        // If scheduler is not enabled, send SMS immediately
        if (!$schedulerEnabled) {
            //echo "<br>Scheduler disabled - sending immediately";
            return $this->apiCall($payload, $mobilenumber, $observerName, $testing, $otp);
        }

        // Get restriction times
        $restrictFromTime = $this->scopeConfig->getValue(
            self::SMS_SCHEDULER_RESTRICT_FROM_TIME, 
            ScopeInterface::SCOPE_STORE
        );
        
        $restrictToTime = $this->scopeConfig->getValue(
            self::SMS_SCHEDULER_RESTRICT_TO_TIME, 
            ScopeInterface::SCOPE_STORE
        );

        //echo "<br>Restriction From: " . ($restrictFromTime ?: 'Not Set');
        //echo "<br>Restriction To: " . ($restrictToTime ?: 'Not Set');

        // If no restriction times set, send immediately
        if (!$restrictFromTime || !$restrictToTime) {
            //echo "<br>No restriction times set - sending immediately";
            return $this->apiCall($payload, $mobilenumber, $observerName, $testing, $otp);
        }

        // Get configured timezone (default to Asia/Kolkata)
        $configuredTimezone = $this->scopeConfig->getValue(
            self::SMS_SCHEDULER_TIMEZONE, 
            ScopeInterface::SCOPE_STORE
        ) ?: 'Asia/Kolkata';

        // Get current time in configured timezone
        $currentDateTime = $this->timezone->date(null, null, $configuredTimezone);
        $currentTime = $currentDateTime->format('H:i');

        //echo "<br>Current Time: " . $currentTime . " (Timezone: " . $configuredTimezone . ")";

        // Convert times to comparable format
        $currentTimeMinutes = $this->timeToMinutes($currentTime);
        $restrictFromMinutes = $this->timeToMinutes($restrictFromTime);
        $restrictToMinutes = $this->timeToMinutes($restrictToTime);

        //echo "<br>Current Minutes: " . $currentTimeMinutes;
        //echo "<br>Restrict From Minutes: " . $restrictFromMinutes;
        //echo "<br>Restrict To Minutes: " . $restrictToMinutes;

        $this->customLog("SMS Scheduler Check - Current: {$currentTime}, Restrict: {$restrictFromTime} to {$restrictToTime}");

        $isRestrictedTime = false;
        
        // Handle time range spanning midnight (e.g., 21:00 to 10:00)
        if ($restrictFromMinutes > $restrictToMinutes) {
            // Restriction spans midnight
            $isRestrictedTime = ($currentTimeMinutes >= $restrictFromMinutes || $currentTimeMinutes <= $restrictToMinutes);
        } else {
            // Normal time range within same day
            $isRestrictedTime = ($currentTimeMinutes >= $restrictFromMinutes && $currentTimeMinutes <= $restrictToMinutes);
        }

        if ($isRestrictedTime) {
            // Queue the SMS
            //echo "<br>queued due to time restriction";
            $this->customLog("SMS queued due to time restriction: {$observerName}");
            return $this->queueSms($payload, $mobilenumber, $observerName);
        } else {
            // Send immediately
            //echo "<br>sent immediately as not in restricted time";
            $this->customLog("SMS sent immediately: {$observerName}");
            return $this->apiCall($payload, $mobilenumber, $observerName, $testing, $otp);
        }
    }

    /**
     * Convert time string to minutes since midnight
     *
     * @param string $time
     * @return int
     */
    private function timeToMinutes($time)
    {
        // Handle different time formats: HH:MM or HH,MM,SS
        $time = str_replace(',', ':', $time); // Convert commas to colons
        $parts = explode(':', $time);
        
        // Take only hours and minutes (ignore seconds if present)
        $hours = intval($parts[0] ?? 0);
        $minutes = intval($parts[1] ?? 0);
        
        //echo "<br>Processing time: '$time' -> Hours: $hours, Minutes: $minutes";
        
        return ($hours * 60) + $minutes;
    }

    /**
     * Queue SMS for later sending
     *
     * @param array $payload
     * @param string $mobilenumber
     * @param string|null $observerName
     * @return bool
     */
    private function queueSms($payload, $mobilenumber, $observerName = null)
    {
        try {
            //echo "<br>Starting queueSms function...";
            
            // Check for duplicate entries within the last 5 minutes
            $payloadJson = json_encode($payload);
            $duplicateCheckTime = date('Y-m-d H:i:s', strtotime('-5 minutes'));
            
            //echo "<br>Checking for duplicates since: " . $duplicateCheckTime;
            //echo "<br>Mobile: " . $mobilenumber . ", Observer: " . $observerName;
            
            // Load existing queue collection to check for duplicates
            $existingQueue = $this->smsQueueFactory->create()->getCollection()
                ->addFieldToFilter('mobile_number', $mobilenumber)
                ->addFieldToFilter('observer_name', $observerName)
                ->addFieldToFilter('message_payload', $payloadJson)
                ->addFieldToFilter('created_at', ['gt' => $duplicateCheckTime])
                ->addFieldToFilter('status', ['in' => [
                    \Meetanshi\Sms\Model\SMSQueue::STATUS_PENDING,
                    \Meetanshi\Sms\Model\SMSQueue::STATUS_SENT
                ]]);
            
            if ($existingQueue->getSize() > 0) {
                //echo "<br>Duplicate SMS found! Skipping queue - already exists with same mobile, observer, and payload";
                $this->customLog("Duplicate SMS prevented for mobile: {$mobilenumber}, observer: {$observerName}");
                return true; // Return true as if it was queued successfully
            }
            //echo "<br>No duplicates found, proceeding with queue...";
            
            $smsQueue = $this->smsQueueFactory->create();
            //echo "<br>SMS Queue object created successfully";
            
            $smsQueue->setMobileNumber($mobilenumber);
            //echo "<br>Mobile number set: " . $mobilenumber;
            
            $payloadJson = json_encode($payload);
            $smsQueue->setMessagePayload($payloadJson);
            //echo "<br>Payload set: " . $payloadJson;
            
            $smsQueue->setObserverName($observerName);
            //echo "<br>Observer name set: " . $observerName;
            
            $smsQueue->setStatus(\Meetanshi\Sms\Model\SMSQueue::STATUS_PENDING);
            //echo "<br>Status set: pending";
            
            $smsQueue->setAttempts(0);
            //echo "<br>Attempts set: 0";
            
            $createdAt = $this->dateTime->gmtDate();
            $smsQueue->setCreatedAt($createdAt);
            //echo "<br>Created at set: " . $createdAt;
            
            // Calculate next send time
            $sendTime = $this->scopeConfig->getValue(
                self::SMS_SCHEDULER_SEND_TIME, 
                ScopeInterface::SCOPE_STORE
            ) ?: '10:01';
            
            //echo "<br>Send time from config: " . $sendTime;
            
            $configuredTimezone = $this->scopeConfig->getValue(
                self::SMS_SCHEDULER_TIMEZONE, 
                ScopeInterface::SCOPE_STORE
            ) ?: 'Asia/Kolkata';
            
            //echo "<br>Configured timezone: " . $configuredTimezone;
            
            // Convert time format from HH,MM,SS to HH:MM
            $sendTime = str_replace(',', ':', $sendTime);
            $sendTimeParts = explode(':', $sendTime);
            $hours = intval($sendTimeParts[0] ?? 10);
            $minutes = intval($sendTimeParts[1] ?? 0);
            
            //echo "<br>Parsed send time: Hours=$hours, Minutes=$minutes";
            
            // Create tomorrow's date and set the send time
            $currentDateTime = $this->timezone->date(null, null, $configuredTimezone);
            $nextSendDate = clone $currentDateTime;
            $nextSendDate->add(new \DateInterval('P1D')); // Add 1 day
            $nextSendDate->setTime($hours, $minutes, 0);
            
            // Convert to GMT for database storage
            $nextSendDate->setTimezone(new \DateTimeZone('UTC'));
            $scheduledAt = $nextSendDate->format('Y-m-d H:i:s');
            
            $smsQueue->setScheduledAt($scheduledAt);
            //echo "<br>Scheduled at set: " . $scheduledAt . " (UTC)";
            
            //echo "<br>About to save SMS queue...";
            $result = $smsQueue->save();
            //echo "<br>Save result: " . print_r($result, true);
            
            // Check if the SMS was actually saved by getting its ID
            $savedId = $smsQueue->getId();
            //echo "<br>Saved SMS Queue ID: " . ($savedId ? $savedId : 'NULL - NOT SAVED!');
            
            if ($savedId) {
                //echo "<br>SMS Queue saved successfully with ID: " . $savedId;
                $this->customLog("SMS queued successfully for mobile: {$mobilenumber} with ID: {$savedId}");
                return true;
            } else {
                //echo "<br>ERROR: SMS Queue was NOT saved to database!";
                $this->customLog("ERROR: Failed to save SMS queue for mobile: {$mobilenumber}");
                return false;
            }
        } catch (\Exception $e) {
            //echo "<br>Error in queueSms: " . $e->getMessage();
            //echo "<br>Stack trace: " . $e->getTraceAsString();
            $this->customLog("Error queuing SMS: " . $e->getMessage());
            return false;
        }
    }
    
    public function apiCall($payload, $mobilenumber, $observerName = null, $testing = 0, $otp = 0)
    {
        if (empty($mobilenumber)) {
            $this->customLog("Phone number not found : {$mobilenumber}");
            return false;
        }

        $mobilenumber = preg_replace('/[^0-9]/', '', $mobilenumber);

        if (strpos($mobilenumber, '91') === 0 && strlen($mobilenumber) > 10) {
            $mobilenumber = substr($mobilenumber, 2); 
        }
        
        $mobilenumber = substr($mobilenumber, 0, 10);

        if (strlen($mobilenumber) !== 10) {
            $this->customLog("Phone number format not 10 digit: {$mobilenumber}");
            return false;
        }

        if (!preg_match('/^[6-9]\d{9}$/', $mobilenumber)) {
            $this->customLog("Phone number format not valid: {$mobilenumber}");
            return false;
        }
        
        $payload['recipients'][0]['mobiles'] = '91' . $mobilenumber;
        
        if (!$testing && !$otp && $observerName) {
            $payloadJson = json_encode($payload);
            $duplicateCheckTime = date('Y-m-d H:i:s', strtotime('-2 minutes'));
            
            $recentHistory = $this->SMSGatewayHistoryFactory->create()->getCollection()
                ->addFieldToFilter('additional_data', ['like' => '%"observer":"' . $observerName . '"%'])
                ->addFieldToFilter('additional_data', ['like' => '%"number":"' . $mobilenumber . '"%'])
                ->addFieldToFilter('sms_body', $payloadJson)
                ->addFieldToFilter('created_at', ['gt' => $duplicateCheckTime]);
            
            if ($recentHistory->getSize() > 0) {
                //echo "<br>Duplicate immediate SMS prevented! Same SMS sent recently.";
                $this->customLog("Duplicate immediate SMS prevented for mobile: {$mobilenumber}, observer: {$observerName}");
                return "DUPLICATE_PREVENTED"; // Return a success-like response
            }
            
        }
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        // try {
            if ($this->smsEnable()) {
                
                $apiProvider = $this->scopeConfig->getValue(self::SMS_APIPROVIDER, ScopeInterface::SCOPE_STORE);
                $apikey = $this->scopeConfig->getValue(self::SMS_APIKEY, ScopeInterface::SCOPE_STORE);
                $apikeyforOther = $this->scopeConfig->getValue(self::SMS_APIKEY_FOR_OTHER, ScopeInterface::SCOPE_STORE);
                $senderid = $this->scopeConfig->getValue(self::SMS_SENDERID, ScopeInterface::SCOPE_STORE);
                $url = $this->scopeConfig->getValue(self::SMS_APIURL, ScopeInterface::SCOPE_STORE);
                $msgtype = $this->scopeConfig->getValue(self::SMS_MSGTYPE, ScopeInterface::SCOPE_STORE);
                $authKey = $this->scopeConfig->getValue('sms/apicredential/apikey_msg91', $storeScope);
                $shortUrl = $this->scopeConfig->getValue('sms/msg91/short_url', $storeScope) ?: 0;
                

                if( $observerName !==  "send_login_otp"){
                    $apiKey = $apikeyforOther;
                }else{
                    $apiKey = urlencode($apikey);
                }

                if ($apiProvider == 'msg91') {
                    
                    $url = $this->scopeConfig->getValue('sms/apicredential/apiurl_msg91', $storeScope);

                        // JSON encode the payload
                        $jsonPayload = json_encode($payload);
                        
                        
                        
                       // Prepare cURL request
                       $ch = curl_init();

                        curl_setopt_array($ch, [
                            CURLOPT_URL => $url,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "POST",
                            CURLOPT_POSTFIELDS => $jsonPayload,
                            CURLOPT_HTTPHEADER => [
                                "accept: application/json",
                                "authkey: " . $authKey,
                                "content-type: application/json"
                            ],
                        ]);

                        $response = curl_exec($ch);
                        $err = curl_error($ch); 
                        curl_close($ch);

                        // Log and save response
                        $mobile['number'] = $mobilenumber;
                        $mobile['observer'] = $observerName;

                        $arr = [];
                        $arr['respose'] = $response;
                        $arr['payload'] = $payload;
                        $arr['additional_data'] = json_encode($mobile);
                        $arr['respose'] = json_encode($arr['respose']);
                        $arr['payload'] = json_encode($arr['payload']);
                        $arr['additional_data'] = json_encode($arr['additional_data']);
                    
                        // Now call the insert function
                        $this->insertValueInHistoryMsg($arr);
                        $this->customLog($response);
                    
                        if ($err) {
                            $this->customLog("cURL Error: " . $err);
                            return false;
                        } else {
                            return $response;
                        }

                }else if ($apiProvider == 'msg1') {
                        $numbers = array($mobilenumber);
                        $sender = urlencode($senderid);

                        $message = rawurlencode( $msg );
                        
                        $numbers = implode(',', $numbers);
                        
                        // Prepare data for POST request

                        $data = array('apikey' => $apiKey, 'numbers' => $numbers, "sender" => $sender, "message" => $message);
                        
                        // Send the POST request with cURL
                        $ch = curl_init($url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_close($ch);
                        $response = curl_exec($ch);

                        $mobile['number'] = $mobilenumber;
                        $mobile['observer'] = $observerName;

                        $arr = [];
                        $arr['respose'] = $response;
                        $arr['sms_body'] = $msg;
                        $arr['additional_data'] = json_encode($mobile);

                        $this->insertValueInHistory($arr);

                        $this->customLog($response);
                        $this->customLog($message);
                    


                       return $response;
                        // return true;


                } else if ($apiProvider == 'twilio') {

                    $sid = $this->scopeConfig->getValue(self::TWILLIOSID, ScopeInterface::SCOPE_STORE);
                    $token = $this->scopeConfig->getValue(self::TWILLIOTOKEN, ScopeInterface::SCOPE_STORE);
                    $number = $this->scopeConfig->getValue(self::TWILLIONUMBER, ScopeInterface::SCOPE_STORE);

                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $twilio = $objectManager->get('\Twilio\Rest\ClientFactory');
                    $client = $twilio->create([
                        'username' => $sid,
                        'password' => $token
                    ]);

                    $response = $client->messages->create(
                        '+' . $mobilenumber,
                        array(
                            'from' => '+' . $number,
                            'body' => $msg
                        )
                    );
                    if ($testing) {
                        return $response;
                    } elseif ($otp) {
                        if ($response->sid) {
                            return 1;
                        } else {
                            return 0;
                        }
                    }
                } else if ($apiProvider == 'bulksms') {


                    $messages = array(
                        array('to' => $mobilenumber, 'body' => $msg)
                    );
                    $post_body = json_encode($messages);
                    $url = "https://api.bulksms.com/v1/messages?auto-unicode=true&longMessageMaxParts=30";
                    $username = $this->scopeConfig->getValue(self::BULKSMS_USERNAME, ScopeInterface::SCOPE_STORE);
                    $password = $this->scopeConfig->getValue(self::BULKSMS_PASSWORD, ScopeInterface::SCOPE_STORE);

                    $ch = curl_init();
                    $headers = array(
                        'Content-Type:application/json',
                        'Authorization:Basic ' . base64_encode("$username:$password")
                    );
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_URL, $url);
                    curl_setopt($ch, CURLOPT_POST, 1);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_body);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

                    $response = curl_exec($ch);
                    $err = curl_error($ch);
                    curl_close($ch);
                    if ($err) {
                        $this->customLog($err);
                        if ($testing) {
                            return $err;
                        } elseif ($otp) {
                            return 0;
                        }
                    } else {
                        $this->customLog($response);
                        if ($testing) {
                            return $response;
                        } elseif ($otp) {
                            return 1;
                        }
                    }

                } else if ($apiProvider == 'other') {
                    $customUrl = $this->scopeConfig->getValue(self::SMS_URL, ScopeInterface::SCOPE_STORE);
                    $msg = urlencode($msg);
                    $codes = ['{mobile}', '{msg}'];
                    $accurate = [$mobilenumber, $msg];
                    $postUrl = str_replace($codes, $accurate, $customUrl);

                    $curl = curl_init();

                    curl_setopt_array($curl, [
                        CURLOPT_URL => $postUrl,
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_ENCODING => "",
                        CURLOPT_MAXREDIRS => 10,
                        CURLOPT_TIMEOUT => 30,
                        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                        CURLOPT_CUSTOMREQUEST => "GET",
                        CURLOPT_SSL_VERIFYHOST => 0,
                        CURLOPT_SSL_VERIFYPEER => 0,
                    ]);

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if ($err) {
                        $this->customLog($err);
                        if ($testing) {
                            return $err;
                        } elseif ($otp) {
                            return 0;
                        }
                    } else {
                        $this->customLog($response);
                        if ($testing) {
                            return $response;
                        } elseif ($otp) {
                            return 1;
                        }
                    }
                }
            }
    }

    /**
     * @param $log
     * @throws \Zend_Log_Exception
     */
    public function customLog($log)
    {
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/sms.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info(print_r($log, true));
    }

    /**
     * @param $countryid
     * @return string
     */
    public function getCountryCode($countryid)
    {
        $countryArray = [
            'AD' => ['name' => 'ANDORRA', 'code' => '376'],
            'AE' => ['name' => 'UNITED ARAB EMIRATES', 'code' => '971'],
            'AF' => ['name' => 'AFGHANISTAN', 'code' => '93'],
            'AG' => ['name' => 'ANTIGUA AND BARBUDA', 'code' => '1268'],
            'AI' => ['name' => 'ANGUILLA', 'code' => '1264'],
            'AL' => ['name' => 'ALBANIA', 'code' => '355'],
            'AM' => ['name' => 'ARMENIA', 'code' => '374'],
            'AN' => ['name' => 'NETHERLANDS ANTILLES', 'code' => '599'],
            'AO' => ['name' => 'ANGOLA', 'code' => '244'],
            'AQ' => ['name' => 'ANTARCTICA', 'code' => '672'],
            'AR' => ['name' => 'ARGENTINA', 'code' => '54'],
            'AS' => ['name' => 'AMERICAN SAMOA', 'code' => '1684'],
            'AT' => ['name' => 'AUSTRIA', 'code' => '43'],
            'AU' => ['name' => 'AUSTRALIA', 'code' => '61'],
            'AW' => ['name' => 'ARUBA', 'code' => '297'],
            'AZ' => ['name' => 'AZERBAIJAN', 'code' => '994'],
            'BA' => ['name' => 'BOSNIA AND HERZEGOVINA', 'code' => '387'],
            'BB' => ['name' => 'BARBADOS', 'code' => '1246'],
            'BD' => ['name' => 'BANGLADESH', 'code' => '880'],
            'BE' => ['name' => 'BELGIUM', 'code' => '32'],
            'BF' => ['name' => 'BURKINA FASO', 'code' => '226'],
            'BG' => ['name' => 'BULGARIA', 'code' => '359'],
            'BH' => ['name' => 'BAHRAIN', 'code' => '973'],
            'BI' => ['name' => 'BURUNDI', 'code' => '257'],
            'BJ' => ['name' => 'BENIN', 'code' => '229'],
            'BL' => ['name' => 'SAINT BARTHELEMY', 'code' => '590'],
            'BM' => ['name' => 'BERMUDA', 'code' => '1441'],
            'BN' => ['name' => 'BRUNEI DARUSSALAM', 'code' => '673'],
            'BO' => ['name' => 'BOLIVIA', 'code' => '591'],
            'BR' => ['name' => 'BRAZIL', 'code' => '55'],
            'BS' => ['name' => 'BAHAMAS', 'code' => '1242'],
            'BT' => ['name' => 'BHUTAN', 'code' => '975'],
            'BW' => ['name' => 'BOTSWANA', 'code' => '267'],
            'BY' => ['name' => 'BELARUS', 'code' => '375'],
            'BZ' => ['name' => 'BELIZE', 'code' => '501'],
            'CA' => ['name' => 'CANADA', 'code' => '1'],
            'CC' => ['name' => 'COCOS (KEELING) ISLANDS', 'code' => '61'],
            'CD' => ['name' => 'CONGO, THE DEMOCRATIC REPUBLIC OF THE', 'code' => '243'],
            'CF' => ['name' => 'CENTRAL AFRICAN REPUBLIC', 'code' => '236'],
            'CG' => ['name' => 'CONGO', 'code' => '242'],
            'CH' => ['name' => 'SWITZERLAND', 'code' => '41'],
            'CI' => ['name' => 'COTE D IVOIRE', 'code' => '225'],
            'CK' => ['name' => 'COOK ISLANDS', 'code' => '682'],
            'CL' => ['name' => 'CHILE', 'code' => '56'],
            'CM' => ['name' => 'CAMEROON', 'code' => '237'],
            'CN' => ['name' => 'CHINA', 'code' => '86'],
            'CO' => ['name' => 'COLOMBIA', 'code' => '57'],
            'CR' => ['name' => 'COSTA RICA', 'code' => '506'],
            'CU' => ['name' => 'CUBA', 'code' => '53'],
            'CV' => ['name' => 'CAPE VERDE', 'code' => '238'],
            'CX' => ['name' => 'CHRISTMAS ISLAND', 'code' => '61'],
            'CY' => ['name' => 'CYPRUS', 'code' => '357'],
            'CZ' => ['name' => 'CZECH REPUBLIC', 'code' => '420'],
            'DE' => ['name' => 'GERMANY', 'code' => '49'],
            'DJ' => ['name' => 'DJIBOUTI', 'code' => '253'],
            'DK' => ['name' => 'DENMARK', 'code' => '45'],
            'DM' => ['name' => 'DOMINICA', 'code' => '1767'],
            'DO' => ['name' => 'DOMINICAN REPUBLIC', 'code' => '1809'],
            'DZ' => ['name' => 'ALGERIA', 'code' => '213'],
            'EC' => ['name' => 'ECUADOR', 'code' => '593'],
            'EE' => ['name' => 'ESTONIA', 'code' => '372'],
            'EG' => ['name' => 'EGYPT', 'code' => '20'],
            'ER' => ['name' => 'ERITREA', 'code' => '291'],
            'ES' => ['name' => 'SPAIN', 'code' => '34'],
            'ET' => ['name' => 'ETHIOPIA', 'code' => '251'],
            'FI' => ['name' => 'FINLAND', 'code' => '358'],
            'FJ' => ['name' => 'FIJI', 'code' => '679'],
            'FK' => ['name' => 'FALKLAND ISLANDS (MALVINAS)', 'code' => '500'],
            'FM' => ['name' => 'MICRONESIA, FEDERATED STATES OF', 'code' => '691'],
            'FO' => ['name' => 'FAROE ISLANDS', 'code' => '298'],
            'FR' => ['name' => 'FRANCE', 'code' => '33'],
            'GA' => ['name' => 'GABON', 'code' => '241'],
            'GB' => ['name' => 'UNITED KINGDOM', 'code' => '44'],
            'GD' => ['name' => 'GRENADA', 'code' => '1473'],
            'GE' => ['name' => 'GEORGIA', 'code' => '995'],
            'GH' => ['name' => 'GHANA', 'code' => '233'],
            'GI' => ['name' => 'GIBRALTAR', 'code' => '350'],
            'GL' => ['name' => 'GREENLAND', 'code' => '299'],
            'GM' => ['name' => 'GAMBIA', 'code' => '220'],
            'GN' => ['name' => 'GUINEA', 'code' => '224'],
            'GQ' => ['name' => 'EQUATORIAL GUINEA', 'code' => '240'],
            'GR' => ['name' => 'GREECE', 'code' => '30'],
            'GT' => ['name' => 'GUATEMALA', 'code' => '502'],
            'GU' => ['name' => 'GUAM', 'code' => '1671'],
            'GW' => ['name' => 'GUINEA-BISSAU', 'code' => '245'],
            'GY' => ['name' => 'GUYANA', 'code' => '592'],
            'HK' => ['name' => 'HONG KONG', 'code' => '852'],
            'HN' => ['name' => 'HONDURAS', 'code' => '504'],
            'HR' => ['name' => 'CROATIA', 'code' => '385'],
            'HT' => ['name' => 'HAITI', 'code' => '509'],
            'HU' => ['name' => 'HUNGARY', 'code' => '36'],
            'ID' => ['name' => 'INDONESIA', 'code' => '62'],
            'IE' => ['name' => 'IRELAND', 'code' => '353'],
            'IL' => ['name' => 'ISRAEL', 'code' => '972'],
            'IM' => ['name' => 'ISLE OF MAN', 'code' => '44'],
            'IN' => ['name' => 'INDIA', 'code' => '91'],
            'IQ' => ['name' => 'IRAQ', 'code' => '964'],
            'IR' => ['name' => 'IRAN, ISLAMIC REPUBLIC OF', 'code' => '98'],
            'IS' => ['name' => 'ICELAND', 'code' => '354'],
            'IT' => ['name' => 'ITALY', 'code' => '39'],
            'JM' => ['name' => 'JAMAICA', 'code' => '1876'],
            'JO' => ['name' => 'JORDAN', 'code' => '962'],
            'JP' => ['name' => 'JAPAN', 'code' => '81'],
            'KE' => ['name' => 'KENYA', 'code' => '254'],
            'KG' => ['name' => 'KYRGYZSTAN', 'code' => '996'],
            'KH' => ['name' => 'CAMBODIA', 'code' => '855'],
            'KI' => ['name' => 'KIRIBATI', 'code' => '686'],
            'KM' => ['name' => 'COMOROS', 'code' => '269'],
            'KN' => ['name' => 'SAINT KITTS AND NEVIS', 'code' => '1869'],
            'KP' => ['name' => 'KOREA DEMOCRATIC PEOPLES REPUBLIC OF', 'code' => '850'],
            'KR' => ['name' => 'KOREA REPUBLIC OF', 'code' => '82'],
            'KW' => ['name' => 'KUWAIT', 'code' => '965'],
            'KY' => ['name' => 'CAYMAN ISLANDS', 'code' => '1345'],
            'KZ' => ['name' => 'KAZAKSTAN', 'code' => '7'],
            'LA' => ['name' => 'LAO PEOPLES DEMOCRATIC REPUBLIC', 'code' => '856'],
            'LB' => ['name' => 'LEBANON', 'code' => '961'],
            'LC' => ['name' => 'SAINT LUCIA', 'code' => '1758'],
            'LI' => ['name' => 'LIECHTENSTEIN', 'code' => '423'],
            'LK' => ['name' => 'SRI LANKA', 'code' => '94'],
            'LR' => ['name' => 'LIBERIA', 'code' => '231'],
            'LS' => ['name' => 'LESOTHO', 'code' => '266'],
            'LT' => ['name' => 'LITHUANIA', 'code' => '370'],
            'LU' => ['name' => 'LUXEMBOURG', 'code' => '352'],
            'LV' => ['name' => 'LATVIA', 'code' => '371'],
            'LY' => ['name' => 'LIBYAN ARAB JAMAHIRIYA', 'code' => '218'],
            'MA' => ['name' => 'MOROCCO', 'code' => '212'],
            'MC' => ['name' => 'MONACO', 'code' => '377'],
            'MD' => ['name' => 'MOLDOVA, REPUBLIC OF', 'code' => '373'],
            'ME' => ['name' => 'MONTENEGRO', 'code' => '382'],
            'MF' => ['name' => 'SAINT MARTIN', 'code' => '1599'],
            'MG' => ['name' => 'MADAGASCAR', 'code' => '261'],
            'MH' => ['name' => 'MARSHALL ISLANDS', 'code' => '692'],
            'MK' => ['name' => 'MACEDONIA, THE FORMER YUGOSLAV REPUBLIC OF', 'code' => '389'],
            'ML' => ['name' => 'MALI', 'code' => '223'],
            'MM' => ['name' => 'MYANMAR', 'code' => '95'],
            'MN' => ['name' => 'MONGOLIA', 'code' => '976'],
            'MO' => ['name' => 'MACAU', 'code' => '853'],
            'MP' => ['name' => 'NORTHERN MARIANA ISLANDS', 'code' => '1670'],
            'MR' => ['name' => 'MAURITANIA', 'code' => '222'],
            'MS' => ['name' => 'MONTSERRAT', 'code' => '1664'],
            'MT' => ['name' => 'MALTA', 'code' => '356'],
            'MU' => ['name' => 'MAURITIUS', 'code' => '230'],
            'MV' => ['name' => 'MALDIVES', 'code' => '960'],
            'MW' => ['name' => 'MALAWI', 'code' => '265'],
            'MX' => ['name' => 'MEXICO', 'code' => '52'],
            'MY' => ['name' => 'MALAYSIA', 'code' => '60'],
            'MZ' => ['name' => 'MOZAMBIQUE', 'code' => '258'],
            'NA' => ['name' => 'NAMIBIA', 'code' => '264'],
            'NC' => ['name' => 'NEW CALEDONIA', 'code' => '687'],
            'NE' => ['name' => 'NIGER', 'code' => '227'],
            'NG' => ['name' => 'NIGERIA', 'code' => '234'],
            'NI' => ['name' => 'NICARAGUA', 'code' => '505'],
            'NL' => ['name' => 'NETHERLANDS', 'code' => '31'],
            'NO' => ['name' => 'NORWAY', 'code' => '47'],
            'NP' => ['name' => 'NEPAL', 'code' => '977'],
            'NR' => ['name' => 'NAURU', 'code' => '674'],
            'NU' => ['name' => 'NIUE', 'code' => '683'],
            'NZ' => ['name' => 'NEW ZEALAND', 'code' => '64'],
            'OM' => ['name' => 'OMAN', 'code' => '968'],
            'PA' => ['name' => 'PANAMA', 'code' => '507'],
            'PE' => ['name' => 'PERU', 'code' => '51'],
            'PF' => ['name' => 'FRENCH POLYNESIA', 'code' => '689'],
            'PG' => ['name' => 'PAPUA NEW GUINEA', 'code' => '675'],
            'PH' => ['name' => 'PHILIPPINES', 'code' => '63'],
            'PK' => ['name' => 'PAKISTAN', 'code' => '92'],
            'PL' => ['name' => 'POLAND', 'code' => '48'],
            'PM' => ['name' => 'SAINT PIERRE AND MIQUELON', 'code' => '508'],
            'PN' => ['name' => 'PITCAIRN', 'code' => '870'],
            'PR' => ['name' => 'PUERTO RICO', 'code' => '1'],
            'PT' => ['name' => 'PORTUGAL', 'code' => '351'],
            'PW' => ['name' => 'PALAU', 'code' => '680'],
            'PY' => ['name' => 'PARAGUAY', 'code' => '595'],
            'QA' => ['name' => 'QATAR', 'code' => '974'],
            'RO' => ['name' => 'ROMANIA', 'code' => '40'],
            'RS' => ['name' => 'SERBIA', 'code' => '381'],
            'RU' => ['name' => 'RUSSIAN FEDERATION', 'code' => '7'],
            'RW' => ['name' => 'RWANDA', 'code' => '250'],
            'SA' => ['name' => 'SAUDI ARABIA', 'code' => '966'],
            'SB' => ['name' => 'SOLOMON ISLANDS', 'code' => '677'],
            'SC' => ['name' => 'SEYCHELLES', 'code' => '248'],
            'SD' => ['name' => 'SUDAN', 'code' => '249'],
            'SE' => ['name' => 'SWEDEN', 'code' => '46'],
            'SG' => ['name' => 'SINGAPORE', 'code' => '65'],
            'SH' => ['name' => 'SAINT HELENA', 'code' => '290'],
            'SI' => ['name' => 'SLOVENIA', 'code' => '386'],
            'SK' => ['name' => 'SLOVAKIA', 'code' => '421'],
            'SL' => ['name' => 'SIERRA LEONE', 'code' => '232'],
            'SM' => ['name' => 'SAN MARINO', 'code' => '378'],
            'SN' => ['name' => 'SENEGAL', 'code' => '221'],
            'SO' => ['name' => 'SOMALIA', 'code' => '252'],
            'SR' => ['name' => 'SURINAME', 'code' => '597'],
            'ST' => ['name' => 'SAO TOME AND PRINCIPE', 'code' => '239'],
            'SV' => ['name' => 'EL SALVADOR', 'code' => '503'],
            'SY' => ['name' => 'SYRIAN ARAB REPUBLIC', 'code' => '963'],
            'SZ' => ['name' => 'SWAZILAND', 'code' => '268'],
            'TC' => ['name' => 'TURKS AND CAICOS ISLANDS', 'code' => '1649'],
            'TD' => ['name' => 'CHAD', 'code' => '235'],
            'TG' => ['name' => 'TOGO', 'code' => '228'],
            'TH' => ['name' => 'THAILAND', 'code' => '66'],
            'TJ' => ['name' => 'TAJIKISTAN', 'code' => '992'],
            'TK' => ['name' => 'TOKELAU', 'code' => '690'],
            'TL' => ['name' => 'TIMOR-LESTE', 'code' => '670'],
            'TM' => ['name' => 'TURKMENISTAN', 'code' => '993'],
            'TN' => ['name' => 'TUNISIA', 'code' => '216'],
            'TO' => ['name' => 'TONGA', 'code' => '676'],
            'TR' => ['name' => 'TURKEY', 'code' => '90'],
            'TT' => ['name' => 'TRINIDAD AND TOBAGO', 'code' => '1868'],
            'TV' => ['name' => 'TUVALU', 'code' => '688'],
            'TW' => ['name' => 'TAIWAN, PROVINCE OF CHINA', 'code' => '886'],
            'TZ' => ['name' => 'TANZANIA, UNITED REPUBLIC OF', 'code' => '255'],
            'UA' => ['name' => 'UKRAINE', 'code' => '380'],
            'UG' => ['name' => 'UGANDA', 'code' => '256'],
            'US' => ['name' => 'UNITED STATES', 'code' => '1'],
            'UY' => ['name' => 'URUGUAY', 'code' => '598'],
            'UZ' => ['name' => 'UZBEKISTAN', 'code' => '998'],
            'VA' => ['name' => 'HOLY SEE (VATICAN CITY STATE)', 'code' => '39'],
            'VC' => ['name' => 'SAINT VINCENT AND THE GRENADINES', 'code' => '1784'],
            'VE' => ['name' => 'VENEZUELA', 'code' => '58'],
            'VG' => ['name' => 'VIRGIN ISLANDS, BRITISH', 'code' => '1284'],
            'VI' => ['name' => 'VIRGIN ISLANDS, U.S.', 'code' => '1340'],
            'VN' => ['name' => 'VIET NAM', 'code' => '84'],
            'VU' => ['name' => 'VANUATU', 'code' => '678'],
            'WF' => ['name' => 'WALLIS AND FUTUNA', 'code' => '681'],
            'WS' => ['name' => 'SAMOA', 'code' => '685'],
            'XK' => ['name' => 'KOSOVO', 'code' => '381'],
            'YE' => ['name' => 'YEMEN', 'code' => '967'],
            'YT' => ['name' => 'MAYOTTE', 'code' => '262'],
            'ZA' => ['name' => 'SOUTH AFRICA', 'code' => '27'],
            'ZM' => ['name' => 'ZAMBIA', 'code' => '260'],
            'ZW' => ['name' => 'ZIMBABWE', 'code' => '263']
        ];

        if (array_key_exists($countryid, $countryArray)) {
            return $countryArray[$countryid]['code'];
        }
        return '';
    }

    
    public function insertValueInHistory($array){
        $model = $this->SMSGatewayHistoryFactory->create();
        $model->addData([
			"sms_body" => $array['payload'], 
			"additional_data" => $array['additional_data'],
			"response" => $array['respose'],
			]);
        $save = $model->save();
    }
    
    
    public function insertValueInHistoryMsg($array)
    {
        $model = $this->SMSGatewayHistoryFactory->create();

        // Only include keys that are set and not null
        $filteredData = array_filter([
            'sms_body' => $array['payload'] ?? null,
            'additional_data' => $array['additional_data'] ?? null,
            'response' => $array['respose'] ?? null
        ], function ($value) {
            return isset($value) && $value !== null;
        });
        
        $model->addData($filteredData);
        $model->save();
        
    }


}
