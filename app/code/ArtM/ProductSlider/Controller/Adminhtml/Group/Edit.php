<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Group;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::group_save';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $groupId = (int)$this->getRequest()->getParam('group_id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ProductSlider::manage_slider_groups');
        $resultPage->getConfig()->getTitle()->prepend($groupId ? __('Edit Slider Group') : __('New Slider Group'));

        return $resultPage;
    }
}
