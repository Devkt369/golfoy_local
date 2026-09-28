<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

class Edit extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::slider_save';

    public function __construct(
        Context $context,
        private readonly PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Page
    {
        $sliderId = (int)$this->getRequest()->getParam('slider_id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_ProductSlider::manage_sliders');
        $resultPage->getConfig()->getTitle()->prepend($sliderId ? __('Edit Slider') : __('New Slider'));

        return $resultPage;
    }
}
