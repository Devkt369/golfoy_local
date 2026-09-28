<?php
namespace ArtM\ReservedProducts\Controller\Adminhtml\Management;


class Index extends \Magento\Backend\App\Action
{
   protected $resultPageFactory = false;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Reserved Products')));
		$filters = $this->getRequest()->getParam('filters');
        $skuFilter = isset($filters['sku']) ? $filters['sku'] : null;
		if($skuFilter != null){
			die($skuFilter);
		}
		return $resultPage;
	}
}
