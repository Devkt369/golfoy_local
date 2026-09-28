<?php

namespace ArtM\GolfoySocial\Controller\Adminhtml\Post;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_GolfoySocial::posts';

    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();

        $resultPage->setActiveMenu('ArtM_GolfoySocial::posts');

        $resultPage->getConfig()->getTitle()->prepend(__('Golfoy Social Posts'));

        return $resultPage;
    }
}