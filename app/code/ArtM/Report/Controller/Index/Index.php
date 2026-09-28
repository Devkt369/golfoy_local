<?php
namespace ArtM\Report\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $resultPageFactory;
    protected $messageManager;
	
    public function __construct(        
		\Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\Action\Context $context
    ) {		      
		parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;  
        $this->messageManager = $messageManager;
    }    
	public function execute()
	{ 
        $page = $this->resultPageFactory->create();
        $PostValues = $this->getRequest()->getPost();
        if(isset($PostValues['pin']) && $PostValues['pin'] != 'g0lf0y'){
        $this->messageManager->addError("Wrong PIN");}
        $block = $page->getLayout()->getBlock('sales_report_custom');
        $block->setData('PostValues', $PostValues);
        return $page;
	}
    
}