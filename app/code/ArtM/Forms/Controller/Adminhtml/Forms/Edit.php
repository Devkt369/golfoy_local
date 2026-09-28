<?php
namespace ArtM\Forms\Controller\Adminhtml\Forms;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use ArtM\Forms\Model\FormsFactory;

class Edit extends Action
{
    protected $resultPageFactory;
    protected $formsFactory;

    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        FormsFactory $formsFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->formsFactory = $formsFactory;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->formsFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This form no longer exists.'));
                return $this->_redirect('*/*/');
            }
        }

        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(
            $id ? __("Edit Form '%1'", $model->getFormName()) : __('New Form')
        );

        return $resultPage;
    }
}
