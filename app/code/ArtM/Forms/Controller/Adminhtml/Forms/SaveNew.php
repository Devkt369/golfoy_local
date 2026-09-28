<?php
namespace ArtM\Forms\Controller\Adminhtml\Forms;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use ArtM\Forms\Model\FormsFactory;
use ArtM\Forms\Model\ResourceModel\Forms\CollectionFactory as FormsCollectionFactory;

class SaveNew extends Action
{
    protected $formsFactory;
    protected $resultRedirectFactory;
    protected $formsCollectionFactory;

    public function __construct(
        Context $context,
        FormsFactory $formsFactory,
        RedirectFactory $resultRedirectFactory,
        FormsCollectionFactory $formsCollectionFactory
    ) {
        parent::__construct($context);
        $this->formsFactory = $formsFactory;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->formsCollectionFactory = $formsCollectionFactory;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            // Check for unique identifier
            $formIdentifier = $data['form_identifier'];
            $collection = $this->formsCollectionFactory->create();
            $collection->addFieldToFilter('form_identifier', $formIdentifier);
            if ($collection->getSize() > 0) {
                $this->messageManager->addErrorMessage(__('A form with this identifier already exists. Please use a unique identifier.'));
                return $resultRedirect->setPath('*/*/new');
            }
            try {
                $model = $this->formsFactory->create();
                $model->setData([
                    'form_name' => $data['form_name'],
                    'form_identifier' => $data['form_identifier'],
                    'form_description' => $data['form_description'],
                    'form_css_class' => isset($data['form_css_class']) ? $data['form_css_class'] : null,
                    'form_html_id' => isset($data['form_html_id']) ? $data['form_html_id'] : null,
                ]);
                $model->save();
                $this->messageManager->addSuccessMessage(__('Form created successfully.'));
                return $resultRedirect->setPath('*/*/index');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                return $resultRedirect->setPath('*/*/new');
            }
        }
        return $resultRedirect->setPath('*/*/index');
    }
}
