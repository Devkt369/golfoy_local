<?php
namespace ArtM\PartnersAuthentication\Plugin;

use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\UrlInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class ForceLogin
{
    protected $customerSession;
    protected $storeManager;
    protected $resultRedirectFactory;
    protected $responseFactory;
    protected $url;
    protected $scopeConfig;
    const PARTNER_LOGIN_STATUS = 'partnersauthentication/general/enable';


    /**
     * Parners Store front
     *
     * @param Session $customerSession
     * @param StoreManagerInterface $storeManager
     * @param RedirectFactory $resultRedirectFactory
     * @param ResponseFactory $responseFactory
     * @param UrlInterface $url
     */
    public function __construct(
        Session $customerSession,
        StoreManagerInterface $storeManager,
        RedirectFactory $resultRedirectFactory,
        ResponseFactory $responseFactory,
        UrlInterface $url,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customerSession = $customerSession;
        $this->storeManager = $storeManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->responseFactory = $responseFactory;
        $this->url = $url;
        $this->scopeConfig = $scopeConfig;
    }

    public function aroundDispatch(
        FrontControllerInterface $subject,
        callable $proceed,
        RequestInterface $request
    ) {
        
        // return $proceed($request);
        // Get current store code
        $currentStoreCode = $this->storeManager->getStore()->getCode();

        // Get current URL path
        $requestPath = trim($request->getPathInfo(), '/');

        // Skip admin panel requests
        if (strpos($requestPath, 'admin') === 0) {
            return $proceed($request);
        }

        // Skip background AJAX, GraphQL, REST API, and other backend requests
        if ($request->isAjax() || strpos($requestPath, 'graphql') === 0 || strpos($requestPath, 'rest') === 0 || strpos($requestPath, 'api') === 0) {
            return $proceed($request);
        }

        // Skip redirection for login, register, forgot password, and loginbyphone/* URLs
        $allowedPages = [
            'customer/account/login',
            'customer/account/create',
            'customer/account/forgotpassword',
            'contact'
        ];
        if (in_array($requestPath, $allowedPages) || strpos($requestPath, 'loginbyphone/') === 0 || strpos($requestPath, 'contact/') === 0) {
            return $proceed($request);
        }

        $partnerAuthenticationStatus = $this->getConfigValue(self::PARTNER_LOGIN_STATUS);

        // Redirect guest users only for 'partners' store when accessing frontend pages
        if ($currentStoreCode === 'partners' && !$this->customerSession->isLoggedIn() && $partnerAuthenticationStatus) {
            $loginUrl = $this->url->getUrl('customer/account/login');
            $response = $this->responseFactory->create();
            return $response->setRedirect($loginUrl);
        }

        return $proceed($request);
    }

    public function getConfigValue($field)
    {
        return $this->scopeConfig->getValue(
            $field,
            ScopeInterface::SCOPE_STORE
        );
    }
    
}
