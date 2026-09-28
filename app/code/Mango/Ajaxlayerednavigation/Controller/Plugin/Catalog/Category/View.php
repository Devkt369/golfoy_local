<?php
namespace Mango\Ajaxlayerednavigation\Controller\Plugin\Catalog\Category;

use Magento\Framework\ObjectManagerInterface;

class View
{
    /**
     * Object Manager instance
     */
    protected $objectManager = null;
    protected $actionFlag = null;
    protected $isAjaxLayeredNavigation = null;
    protected $helper;
    protected $layout;
    protected $jsonHelper;
    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        \Magento\Framework\App\ActionFlag $actionFlag,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\Framework\View\LayoutInterface $layout,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->objectManager = $objectManager;
        $this->actionFlag = $actionFlag;
        $this->helper = $helper;
        $this->layout = $layout;
        $this->jsonHelper = $jsonHelper;
    }
    
    public function beforeExecute(
        \Magento\Catalog\Controller\Category\View $subject
    ) {
        /* check if extension is enabled always... */
        if ($this->helper->isExtensionEnabled()) {
            /* do not render anything-- needed blocks will be rendered afterExecute... */
            $this->isAjaxLayeredNavigation = $subject->getRequest()->getParam('ajax_request') && $subject->getRequest()->getParam('ajaxlist');
            if ($this->isAjaxLayeredNavigation) {
                $this->actionFlag->set('', 'no-renderLayout', true);
            }
            
            /* insert layered navigation before products list */
            if ($this->helper->getConfigValue("horizontal_only")) {
                $this->layout->getUpdate()->addUpdate('<move element="catalog.leftnav" destination="content" before="category.products"/>');
            }
        }
    }
    public function afterExecute(
        \Magento\Catalog\Controller\Category\View $subject,
        $result
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $result;
        }
        
        if ($this->isAjaxLayeredNavigation && $result instanceof \Magento\Framework\View\Result\Page) {
            $page_contents = $result->getLayout()->renderElement("category.products.list", true);
            $navigation = false;
            if ($subject->getRequest()->getParam('reload_navigation') && $result->getLayout()->hasElement("catalog.leftnav")) {
                $navigation = $result->getLayout()->renderElement("catalog.leftnav", true);
            }
            
            if ($subject->getRequest()->getParam('infinite_scroll')) {/* for infinite scroll plugin */
                /*$_p = (int)$subject->getRequest()->getParam('p');
                $page_contents = str_replace("ajaxlist-reload-product_list", "ajaxlist-reload-product_list-" . $_p, $page_contents);*/
                $page_contents = str_replace('id="ajaxlist-reload-product_list"', 'class="ajaxlist-reload-product_list"', $page_contents);
                $subject->getResponse()->setBody($page_contents);
            } else {
                $subject->getResponse()->representJson(
                    $this->jsonHelper->jsonEncode([
                        "product_list" => $page_contents,
                        "navigation" => $navigation])
                );
            }
        } else {
            return $result;
        }
    }
}
