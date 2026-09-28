<?php
namespace Mango\Ajaxlayerednavigation\Block\Plugin\LayeredNavigation;

class Navigation
{
    protected $helper;
    protected $registry;
    protected $layout;
    protected $request;
    
    public function __construct(
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\LayoutInterface $layout
        #\Magento\Framework\App\Request\Http $request
    ) {
        $this->helper = $helper;
        $this->registry = $registry;
        $this->layout = $layout;
        #$this->request = $request;
    }
    /* assign helper for additional functions */
    public function beforeToHtml(
        \Magento\LayeredNavigation\Block\Navigation $subject
    ) {
        /* check if extension is enabled always... */
        if ($this->helper->isExtensionEnabled()) {
            $subject->assign('ajax_helper', $this->helper); /* return mango helper */
        }
   }

    /* set custom template */
    public function aroundGetTemplate(
        \Magento\LayeredNavigation\Block\Navigation $subject,
        \Closure $proceed
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed();
        }
        /* if horizontal navigation is enabled... */
        if ($this->helper->getConfigValue("horizontal_only")==1) {
            return "Mango_Ajaxlayerednavigation::layer/view-horizontal.phtml";
        } else {
            return "Mango_Ajaxlayerednavigation::layer/view.phtml";
        }
    }
    /* set container for ajax calls */
    public function afterToHtml(
        \Magento\LayeredNavigation\Block\Navigation $subject,
        $result
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $result;
        }
        
        /* remove layered navigation if infinite scroll is enabled and page > 1 */
        if ($this->helper->getConfigValue("enable", "infinite_scroll")==1) {
            $request = $this->helper->getRequest();
            $moduleName = $request->getModuleName();
            $controller = $request->getControllerName();
            $action     = $request->getActionName();
            $page      = (int)$request->getParam('p');
            
            $_request_check = [
            'catalog-category-view',
            'catalogsearch-result-index',
            'catalogsearch-advanced-result'
            ];
            
            $request_info = $moduleName . "-" . $controller  . "-" . $action ;
            
            if (in_array($request_info, $_request_check) && $page > 1) {
                $result = "";
                return $result;
            }
        }
        
        
        
        /* when horizontal layered navigation is enabled,
         * load when requested by the products list at
         * Block/Plugin/Catalog/Product/ListProduct.php */
        /*if($this->helper->getConfigValue("horizontal_only")==1 && !$this->registry->_registry("load_horizontal_layered_navigation")){
            return false;
        }*/
        
        $html = '<div id="ajaxlist-reload-layered-navigation"';
            
        if ($this->helper->getConfigValue("horizontal_only")==1) {
            $html.=' class="horizontal-layered-navigation-block"';
        }
        $html.=  '>' . $result . '</div>';
        
        return $html;
    }
}
