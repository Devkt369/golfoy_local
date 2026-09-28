<?php

namespace ArtM\CanonicalUrls\Controller\Adminhtml\Data;

use ArtM\CanonicalUrls\Model\GetcanonicalurlsFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Delete extends Action
{
    protected $resultPageFactory;
    protected $GetcanonicalurlsFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        GetcanonicalurlsFactory $GetcanonicalurlsFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->GetcanonicalurlsFactory = $GetcanonicalurlsFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        try {
            $id = $this->getRequest()->getParam('id');
            if ($id) {
                $model = $this->GetcanonicalurlsFactory->create()->load($id);
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
