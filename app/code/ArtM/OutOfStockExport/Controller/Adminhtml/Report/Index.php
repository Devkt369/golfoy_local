<?php
namespace ArtM\OutOfStockExport\Controller\Adminhtml\Report;

class Index extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'ArtM_OutOfStockExport::report';

    protected $resultPageFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('ArtM_OutOfStockExport::report');
        $resultPage->getConfig()->getTitle()->prepend(__('Out of Stock Export'));
        return $resultPage;
    }
}