<?php

namespace ArtM\Expertlist\Controller\Adminhtml\Data;

use ArtM\Expertlist\Model\ExpertModelFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Delete extends Action
{
    protected $resultPageFactory;
    protected $SizerollFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        ExpertModelFactory $ExpertModelFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->ExpertModelFactory = $ExpertModelFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        try {
            $id = $this->getRequest()->getParam('id');
            
            if ($id) {
                $model = $this->ExpertModelFactory->create()->load($id);
                
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
