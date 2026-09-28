<?php

namespace ArtM\Layouts\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Registry;

class Noindexfollow implements ObserverInterface
{
    protected $request;

    protected $layoutFactory;

    protected $registry;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\View\Page\Config $layoutFactory,
        Registry $registry
    ) {
        $this->request = $request;
        $this->layoutFactory = $layoutFactory;
        $this->registry = $registry;
    }
    public function execute(Observer $observer)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig   = $objectManager->create('Magento\Framework\App\Config\ScopeConfigInterface');
        $fullActionName = $observer->getFullActionName();
        $currentLayout = $this->layoutFactory->getPageLayout();
        $category = $this->registry->registry('current_category');

        if ($category) {
            $strIds = $scopeConfig->getValue(
                'layout/general/category_no_index',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );

            $strIds = trim($strIds ?? '');

            if ($strIds !== '') {
                $ids = array_map('trim', explode(',', $strIds));

                if (in_array($category->getId(), $ids)) {
                    $this->layoutFactory->setRobots('NOINDEX,FOLLOW');
                }
            }
        }

        if ($currentLayout == "2columns-left-noindexing") {
            $this->layoutFactory->setRobots('NOINDEX,FOLLOW');
            $this->layoutFactory->addBodyClass('page-layout-2columns-left');
        }
        $product = $this->registry->registry('current_product');
        if ($product) {
            $strIds        = $scopeConfig->getValue(
                'layout/general/products_no_index',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $strIds =   trim($strIds ?? "");
            if ($strIds != '') {
                $ids = explode(',', $strIds);
                if (in_array($product->getId(), $ids)) {
                    $this->layoutFactory->setRobots('NOINDEX,FOLLOW');
                }
            }
        }
        if ($fullActionName == 'cms_page_view') {
            $cmsPage = $objectManager->get('\Magento\Cms\Model\Page');
            $strIds        = $scopeConfig->getValue(
                'layout/general/cms_no_index',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE
            );
            $strIds =   trim($strIds ?? "");
            if ($strIds != '') {
                $ids = explode(',', $strIds);
                if (in_array($cmsPage->getId(), $ids)) {
                    $this->layoutFactory->setRobots('NOINDEX,FOLLOW');
                }
            }
        }
    }
}
