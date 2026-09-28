<?php
namespace Mango\Ajaxlayerednavigation\Block\Plugin\LayeredNavigation\Navigation;

class State
{
    /* assign own custom template... */
    protected $helper;
    
    public function __construct(
        \Mango\Ajaxlayerednavigation\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }
    
    public function aroundGetTemplate(
        \Magento\LayeredNavigation\Block\Navigation\State $subject,
        \Closure $proceed
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed();
        }
        return "Mango_Ajaxlayerednavigation::layer/state.phtml";
    }
}
