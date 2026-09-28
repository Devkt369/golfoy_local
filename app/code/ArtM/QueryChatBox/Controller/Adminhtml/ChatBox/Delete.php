<?php

namespace ArtM\QueryChatBox\Controller\Adminhtml\ChatBox;

use ArtM\QueryChatBox\Model\QueryChatBoxFactory;
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
        QueryChatBoxFactory $QueryChatBoxFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->QueryChatBoxFactory = $QueryChatBoxFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirectFactory = $this->resultRedirectFactory->create();
        try {
            $id = $this->getRequest()->getParam('id');
            
            if ($id) {
                $model = $this->QueryChatBoxFactory->create()->load($id);
                
                if ($model->getId()) {
                    $model->setData('status', '4'); // Set the new status value
                    $model->save();

                    $this->messageManager->addSuccessMessage(__("Record Added in Fraud/Not-valid query."));
                } else {
                    $this->messageManager->addErrorMessage(__("Something went wrong, Please try again."));
                }
            } else {
                $this->messageManager->addErrorMessage(__("Something went wrong, Please try again."));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("Something went wrong, Please try again."));
        }
        return $resultRedirectFactory->setPath('*/*/chatboxlist');
    }
}
