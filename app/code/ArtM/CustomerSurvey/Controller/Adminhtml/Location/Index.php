<?php

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Location;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::locations';

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

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
        $resultPage->setActiveMenu('ArtM_CustomerSurvey::locations');
        $resultPage->getConfig()->getTitle()->prepend(__('Locations'));

        return $resultPage;
    }
}
