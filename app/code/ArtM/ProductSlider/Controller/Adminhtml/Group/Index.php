<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::manage_slider_groups';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ProductSlider::manage_slider_groups');
        $resultPage->getConfig()->getTitle()->prepend(__('Manage Slider Groups'));

        return $resultPage;
    }
}
