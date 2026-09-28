<?php
namespace ArtM\TradeIn\Controller\Index;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class UpdateStatus extends \Magento\Framework\App\Action\Action
{
    protected $scopeConfig;
    protected $_pageFactory;
    protected $resultFactory;
    
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
	    \Magento\Customer\Model\Session $session,
        \ArtM\TradeIn\Model\TradeInFactory $formmodel,
        ScopeConfigInterface $scopeConfig,
        \ArtM\TradeIn\Helper\Data $helper        
    )
    {
        $this->_pageFactory = $pageFactory;
        $this->formmodel = $formmodel;  
        $this->resultJsonFactory = $resultJsonFactory;
	    $this->_session = $session;
	    $this->_helper = $helper;
        $this->scopeConfig = $scopeConfig;
        return parent::__construct($context);
    }
    public function execute()
    {
        
        $id = $this->getRequest()->getParam('id');
        $status = $this->getRequest()->getParam('status');
//        $id = 135;
//        $status = "fyuewvy";
        
        $json = $this->resultJsonFactory->create();
        
        $formmodelvar = $this->formmodel->create()->load($id);
        
        if ($formmodelvar->getId()) {
            $message = "id found";
            
            $formmodelvar->setData('status', $status);
            if( $formmodelvar->save() ){
                $message = "saved";
            }else{
                $message = "not saved";
            }
        }else{
            $message = "id not found";
        }
        
        return $json->setData([ 'message' => $message ]);
    }
    
}

