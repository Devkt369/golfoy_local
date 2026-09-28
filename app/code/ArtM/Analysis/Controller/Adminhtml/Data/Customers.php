<?php

namespace ArtM\Analysis\Controller\Adminhtml\Data;

class Customers extends \Magento\Backend\App\Action
{
	protected $resultPageFactory = false;
	protected $helper;
	protected $request;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\ArtM\Analysis\Helper\Data $helper,
		\Magento\Framework\App\RequestInterface $request,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->helper            = $helper;
		$this->request           = $request;
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage        = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Customers Performance')));
		
		$type = 'all';
		$brandselected = '';
        if($this->request->getParam('type')){
			$type = $this->request->getParam('type');
		}	
		
		 
		$resultPage->getLayout()->getBlock('analysis.customers')->setData(['type'=> $type]);	
		return $resultPage;
	}
}
?>