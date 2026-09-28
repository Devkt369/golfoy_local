<?php

namespace ArtM\RecommendedSort\Controller\Adminhtml\Manual;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use ArtM\RecommendedSort\Logger\Logger;
use ArtM\RecommendedSort\Helper\Data as HelperData;
use ArtM\RecommendedSort\Model\SortingCategoryProductsFactory;
use ArtM\RecommendedSort\Logger\SortingLogLogger;

class Delete extends Action
{
    protected $resultPageFactory;
    protected $logger;
    protected $helperData;
    protected $sortingCategoryProductsFactory;
    protected $sortingLogLogger;

    public function __construct(
        Context $context,
        Logger $logger,
        HelperData $helperData,
        SortingCategoryProductsFactory $sortingCategoryProductsFactory,
        SortingLogLogger $sortingLogLogger,
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->helperData = $helperData;
        $this->sortingCategoryProductsFactory = $sortingCategoryProductsFactory;
        $this->sortingLogLogger = $sortingLogLogger;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if (!$id) {
            $this->messageManager->addErrorMessage(__('ID is required.'));
            $resultRedirectFactory = $this->resultRedirectFactory->create();
            return $resultRedirectFactory->setPath('artm_recommendedsort/manual/index');
        }
        try {
            $model = $this->sortingCategoryProductsFactory->create();
            $model->load($id);
            $model->delete();

            $this->sortingLogLogger->info("manual Points row deleted for product id ".$model->getProductId()." and category id ".$model->getCategoryId());

            $this->messageManager->addSuccessMessage(__('The product has been deleted.'));
        } catch (\Exception $e) {
            $this->logger->critical($e);
            $this->messageManager->addErrorMessage(__('An error occurred while deleting the product.'));
            $this->sortingLogLogger->info("Error deleting manual Points row for ID: {$id}. Error: " . $e->getMessage());
        }
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        return $resultRedirectFactory->setPath('artm_recommendedsort/manual/index');
    }
}
