<?php
namespace Mango\Ajaxlayerednavigation\Block\Plugin\Catalog\Product;

class ListProduct
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $url;
    protected $helper;
    protected $registry;
    protected $layout;
    
    /**
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\UrlInterface $url,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->url = $url;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->layout = $layout;
    }
    public function afterToHtml(\Magento\Catalog\Block\Product\ListProduct $subject, $result)
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $result;
        }
        
        /* only for products list in main column */
        if (!in_array($subject->getNameInLayout(), ["category.products.list","search_result_list"])) {
            return $result;
        }
        
        $html = "";
        
        $html.= '<div id="ajaxlist-reload-product_list">' . $result ;
        /* include current url in order to update the pagination widget current url... */
        $html.= '<script type="text/javascript">var _ajaxlayerednavigation_current_url= "'
            . $this->_getToolbarUrl()
            . '"</script>';
        
        /* insert next page for infinite scroll feature */
        if ($this->helper->getConfigValue("enable", "infinite_scroll")==1) {
            $_pager = $subject->getToolbarBlock()->getChildBlock("product_list_toolbar_pager");
            if ($_pager && $_pager->getCollection() && !$_pager->isLastPage()) {
                $_i = $_pager->getPagerUrl([ $_pager->getPageVarName() => $_pager->getCurrentPage() + 1 ]);
                
                /* additional parameters if auto-trigger is disabled */
                $_button_text = $this->helper->getConfigValue("button_text", "infinite_scroll");
                $_css_class = $this->helper->getConfigValue("css_class", "infinite_scroll");
                $_aligment = " style='text-align: "
                    . $this->helper->getConfigValue("button_alignment", "infinite_scroll") . ";' ";
                
                $html.= '<div class="infinite-scroll-page-link-container" ' . $_aligment . '>'
                    . '<a href="' . $_i . '" class="ajaxcatalog-infinite-scroll-next '. $_css_class .'">'
                    . '<span>' . $_button_text . '</span></a></div>';
            }
        }
        
        $html.= '</div>';
        return $html;
    }
    /* todo: get one function to remove the url parameters... */
    protected function _getToolbarUrl()
    {
        $urlParams = [
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => [
                'ajax_request' => null,
                'ajaxlist' => null,
                'reload_navigation' => null,
                '_' => null
            ],
            '_escape' => false,
        ];
        return $this->url->getUrl('*/*/*', $urlParams);
    }
}
