<?php

namespace ArtM\TradeIn\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use ArtM\TradeIn\Model\TradeInFactory;

class Delete extends Action
{
    protected $tradeInFactory;

    public function __construct(
        Context $context,
        TradeInFactory $tradeInFactory
    ) {
        parent::__construct($context);
        $this->tradeInFactory = $tradeInFactory;
    }

    /**
     * Delete action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('tradein_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        
        if ($id) {
            try {
                $model = $this->tradeInFactory->create();
                $model->load($id);
                if ($model->getId()) {
                    $model->delete();
                    $this->messageManager->addSuccessMessage(__('Trade-in record deleted successfully.'));
                } else {
                    $this->messageManager->addErrorMessage(__('Trade-in record not found.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('Error deleting trade-in record: %1', $e->getMessage()));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Invalid trade-in ID.'));
        }
        
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Check permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_TradeIn::tradein');
    }
}
