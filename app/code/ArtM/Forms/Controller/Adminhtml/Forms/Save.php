<?php
namespace ArtM\Forms\Controller\Adminhtml\Forms;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use ArtM\Forms\Model\FormsFactory;

class Save extends Action
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
        $data = $this->getRequest()->getPostValue();
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $seenCombinations = [];
        $duplicates = [];

        if (!isset($data['form_fields'])) {
            $this->messageManager->addErrorMessage(__('No form fields data to save.'));
            return $resultRedirect->setPath('*/*/edit', ['id' => $data['id']]);
        }

        foreach ($data['form_fields'] as $field) {
            $combinationKey = $field['name'];
            if (isset($seenCombinations[$combinationKey])) {
                $duplicates[] = $field;
            } else {
                $seenCombinations[$combinationKey] = true;
            }
        }


        if ($data) {
            $id = isset($data['id']) ? $data['id'] : null;
            $model = $this->formsFactory->create();
            if ($id) {
                $model->load($id);
                if (!$model->getId()) {
                    $this->messageManager->addErrorMessage(__('This form no longer exists.'));
                    return $resultRedirect;
                }
            }
            // Save form_fields as JSON
            if (isset($data['form_fields'])) {
                
                if (!empty($duplicates)) {
                    // print_r($duplicates['0']['name']);
                    
                    ;
                    $firstDuplicate = $duplicates[0];
                    $duplicateName = $firstDuplicate['name'];
                    $message = __('A duplicate field was found. The combination of Name "%1" is already in use. Please ensure all field unique name.', $duplicateName);
                    
                    $this->messageManager->addErrorMessage($message);                    
                    $redirectId = $model->getId();
                    return $resultRedirect->setPath('*/*/edit', ['id' => $redirectId]);
                } else {
                    $data['form_fields'] = json_encode($data['form_fields']);
                }
                
            }
            // Ensure captcha fields are always set
            $data['form_captcha_enabled'] = isset($data['form_captcha_enabled']) ? (int)$data['form_captcha_enabled'] : 0;
            $data['form_captcha_type'] = isset($data['form_captcha_type']) ? $data['form_captcha_type'] : null;
            $model->setData($data);
            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('The form has been saved.'));
                // Redirect to edit page for the same form
                $redirectId = $model->getId();
                $resultRedirect->setPath('*/*/edit', ['id' => $redirectId]);
                return $resultRedirect;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }
        $this->messageManager->addErrorMessage(__('Unable to save the form.'));
        return $resultRedirect;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('ArtM_Forms::save');
    }
}
