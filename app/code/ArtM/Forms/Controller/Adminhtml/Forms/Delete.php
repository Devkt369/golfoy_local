<?php
namespace ArtM\Forms\Controller\Adminhtml\Forms;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use ArtM\Forms\Model\FormsFactory;
use Magento\Framework\App\RequestInterface;

class Delete extends Action
{
    protected $formsFactory;

    public function __construct(
        Action\Context $context,
        FormsFactory $formsFactory
    ) {
        $this->formsFactory = $formsFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setPath('*/*/');

        if ($id) {
            try {
                $model = $this->formsFactory->create()->load($id);
                if ($model->getId()) {
                    $model->delete();
                    $this->messageManager->addSuccessMessage(__('The form has been deleted.'));
                } else {
                    $this->messageManager->addErrorMessage(__('Form not found.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        } else {
            $this->messageManager->addErrorMessage(__('Form ID is missing.'));
        }
        return $resultRedirect;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_Forms::delete');
    }
}
