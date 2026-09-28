<?php
namespace ArtM\TradeIn\Helper;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;


use Magento\Framework\App\ScopeResolverInterface;


class Data extends AbstractHelper
{
    protected $customerRepository;

    protected $customerSession;
    
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $storeManager;
    protected $_setupsession;

    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Session\SessionManagerInterface $_setupsession,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
	    $this->_setupsession = $_setupsession;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    public function getLoginCustomerId()
    {
        if ($this->customerSession->isLoggedIn()) {
            $customerId = $this->customerSession->getCustomerId();
            $customer = $this->customerRepository->getById($customerId);
            return $customer->getId();
        }

        return null;
    }
    public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('TradeIn')));

		return $resultPage;
	}
     public function setSessionAdId($value){
        $this->_setupsession->start();
        $this->_setupsession->setSessionAdId($value);
     }
 
    public function getSessionAdId(){
        $this->_setupsession->start();
        return $this->_setupsession->getSessionAdId();
    }
 
    public function unSetSessionAdId(){
        $this->_setupsession->start();
        return $this->_setupsession->unsSessionAdId();
    }
    
    
    public function sendEmail( $to_email, $to_name, $from_email, $from_name, $ad_id , $template_identifier, $productData)
    {
        $templateOptions = [
            'area' => \Magento\Framework\App\Area::AREA_ADMINHTML,
            'store' => $this->storeManager->getStore()->getId(),
        ];

        $templateVars = [
            'product_html' => $productData,
            'ad_id' => $ad_id
        ];

        $from = ['email' => $from_email, 'name' => $from_name];
        $to = ['email' => 'mohit@golfoy.com', 'name' => "mohit"];
//        $to = ['email' => $to_email, 'name' => $to_name];
        $toBcc = ['email' => 'contact@golfoy.com', 'name' => $from_name];

        $this->inlineTranslation->suspend();

        $transport = $this->transportBuilder
            ->setTemplateIdentifier($template_identifier) // Replace with your template identifier
            ->setTemplateOptions($templateOptions)
            ->setTemplateVars($templateVars)
            ->setFrom($from)
            ->addTo( $to_email, $to_name )
            ->addBcc($toBcc['email'], $toBcc['name'])
            ->getTransport();

        $transport->sendMessage();

        $this->inlineTranslation->resume();
    }
}

