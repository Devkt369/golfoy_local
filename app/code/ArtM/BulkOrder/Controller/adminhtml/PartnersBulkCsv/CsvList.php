<?php

namespace ArtM\BulkOrder\Controller\Adminhtml\PartnersBulkCsv;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class CsvList extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('Partners Bulk CSV List'));
        return $resultPage;
    }
}