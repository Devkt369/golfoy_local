<?php

namespace ArtM\CustomProductDesign\Controller\Adminhtml\ClipartImages;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    protected $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Clip Arts'));

        // $resultPage->getLayout()->getBlock('content')
        //             ->setTemplate('ArtM_CustomProductDesign::ClipartImages/clipart-images.phtml');
        return $resultPage;
    }
}
