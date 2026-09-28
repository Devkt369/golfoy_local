<?php

namespace ArtM\Analysis\Controller\Adminhtml\Data;

class Sales extends \Magento\Backend\App\Action
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
		$resultPage->getConfig()->getTitle()->prepend((__('Sales Performance')));

		$type = 'all';
		$attr = '';
		$brandselected = '';
        if($this->request->getParam('type')){
			$type = $this->request->getParam('type');
		}	
		if($this->request->getParam('attr')){
			$attr = $this->request->getParam('attr');
		}	
		if($this->request->getParam('brandselected')){
			$brandselected = $this->request->getParam('brandselected');
		}	
		 
		$resultPage->getLayout()->getBlock('analysis.sales')->setData(['type'=> $type, 'attr'=> $attr, 'brandselected'=> $brandselected]);	
		return $resultPage;
	}
}
?>
