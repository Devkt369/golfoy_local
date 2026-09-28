<?php

namespace ArtM\QueryChatBox\Controller\Adminhtml\ChatBox;

use ArtM\QueryChatBox\Model\QueryChatBoxFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\View\Result\PageFactory;
use ArtM\Expertlist\Model\ExpertModelFactory as CollectionFactory;
use ArtM\QueryChatBox\Helper\Data;

class Save extends Action
{

    protected $expertModelFactory;
    protected $QueryChatBoxFactory;
    protected $formKeyValidator;
    protected $_QueryChatBoxFactory;
    protected $resultPageFactory;
    protected $dataHelper;

    /**
     * Undocumented function
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param QueryChatBoxFactory $QueryChatBoxFactory
     * @param Validator $formKeyValidator
     * @param CollectionFactory $expertModelFactory
     * @param Data $dataHelper
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        QueryChatBoxFactory $QueryChatBoxFactory,
        Validator $formKeyValidator,
        CollectionFactory $expertModelFactory,
        Data $dataHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->_QueryChatBoxFactory = $QueryChatBoxFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->expertModelFactory = $expertModelFactory;
        $this->dataHelper = $dataHelper;
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

        try {
            if ($data) { 
                $modeltoSave = $this->_QueryChatBoxFactory->create();
                $model = $modeltoSave->load($data['id']);
                if($model->getData('query_assign') != $data['query_assign']){

                    $expertCollection = $this->expertModelFactory->create()->getCollection();
                    $expertCollection->addFieldToFilter('id', $data['query_assign']);
                    $expert = $expertCollection->getFirstItem();
                    $this->dataHelper->sendExpertMail($data, $expert['expert_email']);
                    $data['status'] = 5;
                }
                if(!empty($data['response_result'])){
                    $data['status'] = 3;
                }
                date_default_timezone_set("UTC");  
                $currentdate = date('Y-m-d H:i:s' );
                $data['last_update'] = $currentdate;
                $modeltoSave->setData($data)->save();
                $this->messageManager->addSuccessMessage(__("Data Saved Successfully."));
                $buttondata = $this->getRequest()->getParam('back');
                if ($buttondata == 'add') {
                    return $resultPageFactory->setPath('*/*/form');
                }
                if ($buttondata == 'close') {
                    return $resultPageFactory->setPath('*/*/chatboxlist');
                }
                $id = $modeltoSave->getId();
                return $resultPageFactory->setPath('*/*/form', ['id' => $id]);
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e, __("We can't submit your request, Please try again."));
        }
        return $resultPageFactory->setPath('*/*/index');
    }
}
