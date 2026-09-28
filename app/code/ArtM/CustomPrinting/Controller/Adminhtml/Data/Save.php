<?php

namespace ArtM\CustomPrinting\Controller\Adminhtml\Data;

use ArtM\CustomPrinting\Model\Customprinting;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\View\Result\PageFactory;

class Save extends Action
{
    protected $resultPageFactory;
    protected $Customprinting;
    protected $formKeyValidator;
    
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Customprinting $Customprinting,
        Validator $formKeyValidator
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->Customprinting = $Customprinting;
        $this->formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }
    public function execute()
    {
        $resultPageFactory = $this->resultRedirectFactory->create();
        if (!$this->formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(__("Form key is Invalidate"));
            return $resultPageFactory->setPath('*/*/index');
        }
        $data = $this->getRequest()->getPostValue();        
        $datatosave  = ['attributeset' => $data['attributeset'],
                        'printtype' => $data['printtype'],
                        'textlabel' => $data['textlabel'],
                        'logolabel' => $data['logolabel']        
                       ];
        if(isset($data['id'])){$datatosave['id'] = $data['id'];}
        try {
            if ($datatosave) { 
                //$model = $this->Customprinting->create();
                $model = $this->Customprinting;
                date_default_timezone_set("UTC");  
               // $currentdate = date('Y-m-d H:i:s' );
               // $data['created_at'] = $currentdate;
                $model->setData($datatosave)->save();
                $this->messageManager->addSuccessMessage(__("Data Saved Successfully."));
                $buttondata = $this->getRequest()->getParam('back');
                if ($buttondata == 'add') {
                    return $resultPageFactory->setPath('*/*/form');
                }
                if ($buttondata == 'close') {
                    return $resultPageFactory->setPath('*/*/index');
                }
                $id = $model->getId();
                return $resultPageFactory->setPath('*/*/form', ['id' => $id]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("We can't submit your request, Please try again."));
        }
        return $resultPageFactory->setPath('*/*/index');
    }
}
