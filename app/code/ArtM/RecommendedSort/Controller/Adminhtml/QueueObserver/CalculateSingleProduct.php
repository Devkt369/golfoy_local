<?php
namespace ArtM\RecommendedSort\Controller\Adminhtml\QueueObserver;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\RecommendedSort\Logger\Logger;
use ArtM\RecommendedSort\Helper\Data as HelperData;

class CalculateSingleProduct extends Action
{
    protected $resultPageFactory;
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
        $id = $this->getRequest()->getParam('id');
        $product_id = $this->getRequest()->getParam('product_id');

        $this->messageManager->addSuccessMessage(__(" Single Product Initiated Successfully ".json_encode($product_id)));

        $filters = ["created_date","rating","high_discount","salable_qty","availability_compare","feature","size_roll"];
        $this->helperData->RunForSingleProduct($product_id, json_encode($filters));

        $this->helperData->removeQueueItem($id);
        
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        return $resultRedirectFactory->setPath('artm_recommendedsort/queueobserver/index');
    }
}
