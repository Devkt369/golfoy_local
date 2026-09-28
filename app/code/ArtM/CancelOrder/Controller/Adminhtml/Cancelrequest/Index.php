<?php

namespace ArtM\CancelOrder\Controller\Adminhtml\Cancelrequest;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResponseInterface;
class Index extends Action
{
	private $pageFactory;
	public function __construct(PageFactory $pageFactory, Action\Context $context)
	{
		$this->pageFactory= $pageFactory;
		parent::__construct($context);
	}

	public function execute()
	{
		return $this->pageFactory->create();
	}
}