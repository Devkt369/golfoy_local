<?php
namespace ArtM\TradeIn\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;

class Index extends Action
{
    protected $resultPageFactory = false;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('TradeIn'));

        return $resultPage;
    }
}

 
