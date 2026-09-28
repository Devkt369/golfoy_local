<?php
namespace Shipsy\EcommerceExtension\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\Cookie\PublicCookieMetadata;

class AdminLoginSucceeded implements ObserverInterface
{
    protected $_messageManager;
    protected $_configWriter;
    protected $messageManager;
    protected $cookieManager;
    protected $cookieMetadataFactory;
    protected $sessionManager;
    protected $scopeConfig;
    protected $urlInterface;
    protected $encryptor;
    protected $dataHelper;

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\Message\ManagerInterface $_messageManager,
        \Magento\Framework\Session\SessionManagerInterface $sessionManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $urlInterface,
        \Magento\Framework\App\Config\Storage\WriterInterface $_configWriter,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Shipsy\EcommerceExtension\Helper\Data $dataHelper
    ) {
        $this->_messageManager = $_messageManager;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->scopeConfig = $scopeConfig;
        $this->urlInterface = $urlInterface;
        $this->_configWriter = $_configWriter;
        $this->encryptor = $encryptor;
        $this->dataHelper = $dataHelper;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {

        $duration = 86400;
        $metadata = $this->cookieMetadataFactory
                                ->createPublicCookieMetadata()
                                ->setDuration($duration)
                                ->setPath($this->sessionManager->getCookiePath())
                                ->setDomain($this->sessionManager->getCookieDomain())
                                ->setHttpOnly(false);
        try {
            $data = [
                'username' => $this->scopeConfig->getValue('configuration/services/shipsy_username', \Magento\Store\Model\ScopeInterface::SCOPE_STORE),
                'password' => $this->encryptor->decrypt($this->scopeConfig->getValue('configuration/services/shipsy_password', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)),
            ];

            $organisation_id = $this->scopeConfig->getValue('configuration/services/organisation_id', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $base_url = $this->dataHelper->getBaseUrl($this->scopeConfig, $organisation_id);
        
            if (isset($data['username']) && isset($data['password'])) {
                $data_string = json_encode($data);
                $headers = [
                    'Content-Type: application/json',
                    'organisation-id:'.$organisation_id,
                    'shop-origin:magento',
                    'shop-url:'.$this->urlInterface->getBaseUrl()
                ];

                $ch = curl_init($base_url . '/api/ecommerce/registershop');

                curl_setopt($ch, CURLOPT_POST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $result = curl_exec($ch);
                curl_close($ch);
                $resultdata = json_decode($result, true);
                if (array_key_exists('data', $resultdata)) {
                    if (array_key_exists('access_token', $resultdata['data'])) {
                        $accesstoken = $resultdata["data"]["access_token"];
                    } else {
                        throw new \Exception('Could not get access token. Shipsy extension will not be able to function properly.');
                    }
                    if (array_key_exists('customer', $resultdata['data']) && array_key_exists('id', $resultdata["data"]["customer"]) && array_key_exists('code', $resultdata['data']['customer'])) {
                        $customerId = $resultdata["data"]['customer']['id'];
                        $customerCode = $resultdata["data"]['customer']['code'];
                    } else {
                        throw new \Exception('Could not get customer credentials. Shipsy extension will not be able to function properly.');
                    }
                } else {
                    throw new \Exception('Authorisation error. Shipsy extension will not be able to function properly.');
                }
                
                if ($this->cookieManager->getCookie('access-token-shipsy')) {
                    $this->cookieManager->deleteCookie('access-token-shipsy', $metadata);
                }
                if ($this->cookieManager->getCookie('customer-id')) {
                    $this->cookieManager->deleteCookie('customer-id', $metadata);
                }
                if ($this->cookieManager->getCookie('customer-code')) {
                    $this->cookieManager->deleteCookie('customer-code', $metadata);
                }
                $this->cookieManager->setPublicCookie('access-token-shipsy', $accesstoken, $metadata);
                $this->cookieManager->setPublicCookie('customer-id', $customerId, $metadata);
                $this->cookieManager->setPublicCookie('customer-code', $customerCode, $metadata);
            }
        } catch (\Exception $e) {
            $this->cookieManager->deleteCookie('access-token-shipsy', $metadata);
            $this->cookieManager->deleteCookie('customer-id', $metadata);
            $this->cookieManager->deleteCookie('customer-code', $metadata);
            $this->_messageManager->addError(__('Error Reason: '. $e->getMessage()));
        }
        return $this;
    }
}
