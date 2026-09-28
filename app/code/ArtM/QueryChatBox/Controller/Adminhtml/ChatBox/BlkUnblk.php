<?php

namespace ArtM\QueryChatBox\Controller\Adminhtml\ChatBox;

use ArtM\QueryChatBox\Model\QueryChatBoxFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class BlkUnblk extends Action
{
    protected $resultPageFactory;
    protected $queryChatBoxFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QueryChatBoxFactory $queryChatBoxFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->queryChatBoxFactory = $queryChatBoxFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $id = $this->getRequest()->getParam('id'); 
            $status = $this->getRequest()->getParam('status'); 
            if ($id) {
                $model = $this->queryChatBoxFactory->create()->load($id);

                if ($model->getId()) {
                    date_default_timezone_set("UTC");
                    $currentDate = date('Y-m-d H:i:s');
                    $model->setLastUpdate($currentDate); // Update last update date
                    $model->setBlkUnblkUser($status);       // Set block/unblock status
                    
                    $model->save();
                    if($status){
                        $this->messageManager->addSuccessMessage(__("User has been successfully blocked."));
                    }
                    $this->messageManager->addSuccessMessage(__("User has been successfully Unblocked."));
                } else {
                    $this->messageManager->addErrorMessage(__("User not found, Please try again."));
                }
            } else {
                $this->messageManager->addErrorMessage(__("Invalid request, Please try again."));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__("We can't perform the task! Please try again. " . $e->getMessage()));
        }

        return $resultRedirect->setPath('*/*/chatboxlist');
    }
}
