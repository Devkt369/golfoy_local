<?php
/*
 *  toolbar overwrite functions...
 */
namespace Mango\Ajaxlayerednavigation\Block\Plugin\Catalog\Product\ProductList;

class Toolbar
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $helper;
    protected $request;
    protected $infiniteScrollApplied;
    protected $infiniteScrollChecked;
    protected $settings;
    protected $jsonEncoder;
    
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->request = $request;
        $this->jsonEncoder = $jsonEncoder;
    }
    /**
     * Retrieve widget options in json format
     *
     * @param array $customOptions Optional parameter for passing custom selectors from template
     * @return string
     */
    public function aroundGetWidgetOptionsJson(\Magento\Catalog\Block\Product\ProductList\Toolbar $subject, \Closure $proceed, array $customOptions = [])
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed($customOptions);
        }
        if ($subject->getTemplate() != "Mango_Ajaxlayerednavigation::scripts.phtml") {
            return json_encode(['productListToolbarForm' => []]);
        }
        
        $info = json_decode($proceed($customOptions), true); /* true returns array */
        
        if (isset($info["productListToolbarForm"])) {
            $data = $info["productListToolbarForm"];
            /* set direction correctly. for catalogsearch */
            $data["directionDefault"] = $subject->getDefaultDirection();
            $general = $this->helper->getConfigSection('general');
            $frontend = $this->helper->getConfigSection('frontend');
            if ($this->helper->getConfigValue('enable', 'infinite_scroll')) {
                $_auto_trigger_until = (int)$this->helper->getConfigValue('auto_trigger_until', 'infinite_scroll');
                $_auto_trigger_until = !$_auto_trigger_until ? false: $_auto_trigger_until;
                $infinite_scroll = [
                    'infinite_scroll' => [
                        'enable' => true,
                        'autoTrigger' => (bool)$this->helper->getConfigValue('auto_trigger', 'infinite_scroll'),
                        'autoTriggerUntil' => $_auto_trigger_until,
                        'padding' =>$this->helper->getConfigValue('padding', 'infinite_scroll'),
                    ]
                ];
            } else {
                $infinite_scroll = [
                    'infinite_scroll' =>[
                        'enable' => false
                    ]
                ];
            }           
            $data = array_merge($data, $general, $frontend, $infinite_scroll);
            /* process multiples sliders configuration */
            if (isset($data["sliders_configuration"])) {
                $data["sliders_configuration"] = $this->helper->getSlidersConfiguration();
            }
            
            if (isset($data["attribute_sliders_configuration"])) {
                $data["attribute_sliders_configuration"] = $this->helper->getAttributeSlidersConfiguration();
            }

            return json_encode($data);
        }
    }
    
    public function beforeToHtml(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject
    ) {
        $subject->setData("url_handler_plugin",  $this->helper->getConfigValue('urlhandler'));
    }
       
    public function afterToHtml(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        $result
    ) {
        
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $result;
        }
        
        /* remove toolbar if infinite scroll is enabled and page > 1 */
        $page      = (int)$this->request->getParam('p');
        if ($this->disableOutputInfiniteScroll() && $page > 1) {
            $result = "";
        }
        
        return $result;
    }
    
    public function afterGetPagerHtml(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        $result
    ) {
        /* remove toolbar if infinite scroll is enabled and page > 1 */
        if ($this->disableOutputInfiniteScroll()) {
                $result = "";
        }
        
        return $result;
    }
    
    public function aroundGetTemplateFile(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed,
        $template = null
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed($template);
        }
        
        if ($this->disableOutputInfiniteScroll()) {
            if ($template == 'Magento_Catalog::product/list/toolbar/amount.phtml') {
                $template = 'Mango_Ajaxlayerednavigation::product/list/toolbar/amount.phtml';
            }
            if ($template == 'Magento_Catalog::product/list/toolbar/limiter.phtml') {
                $template = 'Mango_Ajaxlayerednavigation::product/list/toolbar/limiter.phtml';
            }
        }
        
        return $proceed($template);
    }
    
    public function disableOutputInfiniteScroll()
    {
        
        /* check only once.. */
        if (!$this->infiniteScrollChecked) {
            /* remove toolbar if infinite scroll is enabled and page > 1 */
            $this->infiniteScrollApplied = false;
            if ($this->helper->getConfigValue("enable", "infinite_scroll")==1) {
                $moduleName = $this->request->getModuleName();
                $controller = $this->request->getControllerName();
                $action     = $this->request->getActionName();
                $_request_check = [
                'catalog-category-view',
                'catalogsearch-result-index',
                'catalogsearch-advanced-result'
                ];
                $request_info = $moduleName . "-" . $controller  . "-" . $action ;
                if (in_array($request_info, $_request_check)) {
                    $this->infiniteScrollApplied = true;
                }
            }
            $this->infiniteScrollChecked = true;
        }
        return $this->infiniteScrollApplied;
    }
    
    
        
    /*public function aroundGetFrontendConfiguration(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed
        )
    {
        if (!$this->settings) {
            $general = $this->helper->getConfigSection('general');
            $frontend = $this->helper->getConfigSection('frontend');
            $frontend = $this->helper->getConfigSection('infinite_scroll');
            //$config["button_title"] = addslashes(strip_tags($config["button_title"], "<b><span><i>"));
            ///* fix button title html */
      /*      $this->settings = array_merge($general, $frontend);
        }
        return $this->settings;
    }
    public function aroundGetFrontendConfigurationJson(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed
        )
    {
        return $this->jsonEncoder->encode($this->getFrontendConfiguration());
    }
    /*public function aroundGetUrlHandlerPlugin(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed
        )
    {
        return $this->helper->getConfigValue('urlhandler');
    }*/    
}
