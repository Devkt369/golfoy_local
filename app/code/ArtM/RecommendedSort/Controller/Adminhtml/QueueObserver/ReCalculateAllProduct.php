<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\QueueObserver;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\RecommendedSort\Logger\Logger;
use ArtM\RecommendedSort\Helper\Data as HelperData;

class ReCalculateAllProduct extends Action
{
    protected $resultJsonFactory;
    protected $logger;
    protected $helperData;

    public function __construct(
        Context $context,
        Logger $logger,
        HelperData $helperData
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->helperData = $helperData;
    }

    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        $this->helperData->queueProductRecaclulatePointsProcess();
        $this->messageManager->addSuccessMessage(__("First 10 Product Initiated Successfully "));
        return $resultRedirectFactory->setPath('artm_recommendedsort/queueobserver/index');
    }
}
