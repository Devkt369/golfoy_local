<?php

namespace ArtM\GolfoySocial\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Newpost extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_GolfoySocial::posts';

    public function __construct(
        Context $context,
        private PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu('ArtM_GolfoySocial::posts');
        $resultPage->getConfig()->getTitle()->prepend(__('New Golfoy Social Post'));

        return $resultPage;
    }
}
