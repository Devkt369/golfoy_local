<?php
namespace Mango\Ajaxlayerednavigation\Controller\Plugin\CatalogSearch\Result;

use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Catalog\Model\Session;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Search\Model\QueryFactory;

class Index
{
    /**
     * Object Manager instance
     */
    protected $objectManager = null;
    protected $resultPageFactory = null;
    protected $actionFlag = null;
    protected $helper;
    /**
     * Catalog session
     *
     * @var Session
     */
    protected $catalogSession;
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var QueryFactory
     */
    protected $queryFactory;
    protected $catalogSearchHelper;
    protected $jsonHelper;
    
    /**
     * Catalog Layer Resolver
     *
     * @var Resolver
     */
    private $layerResolver;
    public function __construct(
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        Session $catalogSession,
        StoreManagerInterface $storeManager,
        QueryFactory $queryFactory,
        Resolver $layerResolver,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\CatalogSearch\Helper\Data $catalogSearchHelper,
        \Magento\Framework\Json\Helper\Data $jsonHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->storeManager = $storeManager;
        $this->catalogSession = $catalogSession;
        $this->queryFactory = $queryFactory;
        $this->layerResolver = $layerResolver;
        $this->helper = $helper;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->jsonHelper = $jsonHelper;
    }
    /**
     * Display search result
     *
     * @return void
     */
    public function aroundExecute(
        \Magento\CatalogSearch\Controller\Result\Index $subject,
        \Closure $proceed
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed();
        }
        $is_ajaxlayerednavigation = $subject->getRequest()->getParam('ajax_request') && $subject->getRequest()->getParam('ajaxlist');
        if (!$is_ajaxlayerednavigation) {
            $proceed();
            return;
        }
        $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);
        /* @var $query \Magento\Search\Model\Query */
        $query = $this->queryFactory->get();
        $query->setStoreId($this->storeManager->getStore()->getId());
        $navigation = false;
        $page_contents = false;
        if ($query->getQueryText() != '') {
            if ($this->catalogSearchHelper->isMinQueryLength()) {
                $query->setId(0)->setIsActive(1)->setIsProcessed(1);
            } else {
                $query->saveIncrementalPopularity();
                if ($query->getRedirect()) {
                    $subject->getResponse()->setRedirect($query->getRedirect());
                    return;
                }
            }
            $this->catalogSearchHelper->checkNotes();
            //$this->view->loadLayout();
            //$this->view->renderLayout();
            $resultPage = $this->resultPageFactory->create();
            if ($subject->getRequest()->getParam('reload_navigation') && $resultPage->getLayout()->isBlock("catalogsearch.leftnav")) {
                    $navigation = $resultPage->getLayout()->renderElement("catalogsearch.leftnav", true);
            }
            
            if ($resultPage->getLayout()->isBlock("search_result_list")) {
                $page_contents = $resultPage->getLayout()->renderElement("search_result_list", true);
            }
        } else {
            //$subject->getResponse()->setRedirect($this->redirect->getRedirectUrl());
            $page_contents = "error";
        }
        if ($subject->getRequest()->getParam('infinite_scroll')) {/* for infinite scroll plugin */
            //$_p = (int)$subject->getRequest()->getParam('p');
            $page_contents = str_replace('id="ajaxlist-reload-product_list"', 'class="ajaxlist-reload-product_list"', $page_contents);
            $subject->getResponse()->setBody($page_contents);
        } else {
            $subject->getResponse()->representJson(
                $this->jsonHelper->jsonEncode(["product_list" => $page_contents, "navigation" => $navigation])
            );
        }
    }
}
