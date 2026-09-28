<?php

namespace ArtM\CanonicalUrls\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\View\Page\Config as PageConfig;
use Magento\Framework\App\RequestInterface;
use Magento\Cms\Model\PageFactory;
use ArtM\CanonicalUrls\Helper\Data as CanonicalHelper;

class AddCmsCanonical implements ObserverInterface
{
    protected $pageConfig;
    protected $request;
    protected $pageFactory;
    protected $canonicalHelper;

    public function __construct(
        PageConfig $pageConfig,
        RequestInterface $request,
        PageFactory $pageFactory,
        CanonicalHelper $canonicalHelper
    ) {
        $this->pageConfig = $pageConfig;
        $this->request = $request;
        $this->pageFactory = $pageFactory;
        $this->canonicalHelper = $canonicalHelper;
    }

    public function execute(Observer $observer)
    {
        if ($this->request->getFullActionName() !== 'cms_page_view') {
            return;
        }

        $page = $this->pageFactory->create()->load(
            $this->request->getParam('page_id')
        );

        if (!$page->getId()) {
            return;
        }

        $canonicalUrl = $this->canonicalHelper->getPageRow($page->getId());
        
         if ($canonicalUrl) {
             $this->pageConfig->addRemotePageAsset(
                 $canonicalUrl,
                 'canonical',
                 ['attributes' => ['rel' => 'canonical']]
             );
         }
    }
}