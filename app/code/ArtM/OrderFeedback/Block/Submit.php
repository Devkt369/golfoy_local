<?php
/*namespace ArtM\OrderFeedback\Block;
class Submit extends \Magento\Framework\View\Element\Template
{	
	function customprint(){
		echo "calling customprint";
	}
}*/

namespace ArtM\OrderFeedback\Block;

use ArtM\OrderFeedback\Model\SurveyFactoryFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Submit extends \Magento\Sales\Block\Order\Totals {
    public $_template = 'ArtM_OrderFeedback::feedback.phtml';
    public $objectPaytmCont='';
    protected $checkoutSession;
    
    protected $customerSession;
    protected $_orderFactory;
    
    protected $_storeManager;
    protected $_urlInterface;
    
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $registry,
        SurveyFactoryFactory $SurveyFactoryFactory,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\UrlInterface $urlInterface,
        
        array $data = []
    ) {
        parent::__construct($context, $registry, $data);
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->_orderFactory = $orderFactory;
        $this->SurveyFactoryFactory = $SurveyFactoryFactory;
        $this->_storeManager = $storeManager;
        $this->_urlInterface = $urlInterface;
        $this->_scopeConfig = $scopeConfig;
    }


    public function getBaseUrl(){
        return $this->_storeManager->getStore()->getBaseUrl();
    }
    public function getViewUrl($orderId)
    {
        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }

    public function getFeedbackStatus(){
        return $this->_scopeConfig->getValue(
            'orderFeedback/orderfeedbact_status/status',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function geNumberOfDays(){
        return $this->_scopeConfig->getValue(
            'orderFeedback/orderfeedbact_status/numberofdays',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    public function getOrder()
    {
        return  $this->_order = $this->_orderFactory->create()->loadByIncrementId(
            $this->checkoutSession->getLastRealOrderId());
    }

    public function getCustomerId()
    {
        return $this->customerSession->getCustomer()->getId();
    }
    
    public function isCustomerFirstOrder($customer_id){
        $orderCollection = $this->_orderFactory->create()->getCollection()
        ->addFieldToFilter('customer_id', $customer_id);
        return $orderCollection->getSize();
    }

    public function isSurveyOlder($customer_id)
    {
        $surveyCollection = $this->SurveyFactoryFactory->create()->getCollection()
            ->addFieldToFilter('customer_id', $customer_id)
            ->setOrder('craeted_at', 'DESC') 
            ->setPageSize(1)                 
            ->setCurPage(1);

        $latestSurvey = $surveyCollection->getFirstItem();
        // print_r($latestSurvey->getData());die; //Array ( [customer_id] => 30139 [order_id] => 136834 [survey_results] => {"q1":"1"} {"q2":"very satisfied"} {"q3":"good"} {"q4":"9"} {"q5":"10"} [id] => 1536 [craeted_at] => 2025-10-07 08:30:00 )
        if ($latestSurvey && $latestSurvey->getId()) {
            $createdAt = $latestSurvey->getData('craeted_at');
            $createdTimestamp = strtotime($createdAt);

            $days = $this->geNumberOfDays() . ' days';
            $daysAgo = strtotime($days);

            // ✅ Return true if older than given number of days
            return $createdTimestamp < $daysAgo;
        }

        // No record found → consider as older (so we can create a new one)
        return true;
    }


    public function getFirstQuestion($customer_id){
        $surveyModel = $this->SurveyFactoryFactory->create();
        $surveyCollection = $surveyModel->getCollection()
            ->addFieldToFilter('customer_id', $customer_id)
            ->getFirstItem();
        if(count($surveyCollection->getData()) > 0){
            $data = $surveyCollection->getData('survey_results');
            if($data){
                if(strpos($data,'q1') && !strpos($data,'q2') && !strpos($data,'q3') && !strpos($data,'q4') && !strpos($data,'q5') && !strpos($data,'q6') && !strpos($data,'q7')){
                    return true;
                }else{
                   return false;
                }
            }
        }else{
            return 'norecordfound';
        }

    }
}
