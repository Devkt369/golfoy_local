<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\QueueObserver;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use ArtM\RecommendedSort\Logger\Logger;
use ArtM\RecommendedSort\Helper\Data as HelperData;

class Index extends Action
{
    protected $resultPageFactory;
    protected $logger;
    protected $helperData;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Logger $logger,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->helperData = $helperData;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Recommended Sort Queue'));
        return $resultPage;
    }
}
