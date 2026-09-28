<?php
namespace ArtM\CategoryPage\Controller\Adminhtml\Manual;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CategoryPage::manual_categories';

    private $pageFactory;

    public function __construct(Context $context, PageFactory $pageFactory)
    {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
    }

    public function execute(): Page
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('ArtM_CategoryPage::manual_categories');
        $page->getConfig()->getTitle()->prepend(__('Manual Categories'));
        return $page;
    }
}
