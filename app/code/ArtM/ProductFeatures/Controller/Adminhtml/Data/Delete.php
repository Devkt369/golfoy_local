<?php

namespace ArtM\ProductFeatures\Controller\Adminhtml\Data;

use ArtM\ProductFeatures\Model\GetproductfeaturesFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Delete extends Action
{
    protected $resultPageFactory;
    protected $GetproductfeaturesFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        GetproductfeaturesFactory $GetproductfeaturesFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->GetproductfeaturesFactory = $GetproductfeaturesFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        try {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model = $this->GetproductfeaturesFactory->create()->load($id);
                if ($model->getId()) {
                    $model->delete();
                    $this->messageManager->addSuccessMessage(__("Record Delete Successfully."));
                } else {
                    $this->messageManager->addErrorMessage(__("Something went wrong, Please try again."));
                }
            } else {
                $this->messageManager->addErrorMessage(__("Something went wrong, Please try again."));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("We can't delete record, Please try again."));
        }
        return $resultRedirectFactory->setPath('*/*/index');
    }
}
