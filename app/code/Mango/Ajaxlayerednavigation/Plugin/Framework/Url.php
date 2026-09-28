<?php
namespace Mango\Ajaxlayerednavigation\Plugin\Framework;

class Url
{
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $isQuickview = false;
    protected $redirect = false;
    protected $urlHelper = false;
    protected $currentUrl = null;
    public function __construct(
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\Url\Helper\Data $urlHelper
    ) {
        $this->redirect = $redirect;
        $url_info = false;
        parse_str((string) parse_url($this->redirect->getRefererUrl(), PHP_URL_QUERY), $url_info);
        if (isset($url_info["quickview"]) && (bool) $url_info["quickview"]) {
            $this->isQuickview = true;
        }
        $this->urlHelper = $urlHelper;
    }
    public function beforeGetUrl(\Magento\Framework\Url $subject, $routePath = null, $routeParams = null)
    {
        if (isset($routeParams['_query'])) {
            if (isset($routeParams['_query']['ajaxlist'])) {
                unset($routeParams['_query']['ajaxlist']);
            }
            if (isset($routeParams['_query']['ajax_request'])) {
                unset($routeParams['_query']['ajax_request']);
            }
            if (isset($routeParams['_query']['reload_navigation'])) {
                unset($routeParams['_query']['reload_navigation']);
            }
            if (isset($routeParams['_query']['_'])) {
                unset($routeParams['_query']['_']);
            }
        }
        return [ $routePath, $routeParams];
    }
    
    
    /**
     * Retrieve current url
     * store it in helper not to process multiple times
     *
     * @return string
     */
    public function aroundGetCurrentUrl(\Magento\Framework\Url $subject, \Closure $proceed)
    {
        if (is_null($this->currentUrl)) {
            /* will remove ajaxlist and ajax_request parameters from url */
            $result = $proceed();
            $result = $this->urlHelper->removeRequestParam($result, "ajaxlist");
            $result = $this->urlHelper->removeRequestParam($result, "ajax_request");
            $result = $this->urlHelper->removeRequestParam($result, "reload_navigation");
            $result = $this->urlHelper->removeRequestParam($result, "_");
            $this->currentUrl = $result;
        }
        return $this->currentUrl;
    }
}
