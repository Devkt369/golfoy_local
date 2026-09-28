<?php

namespace ArtM\InvoiceGrid\Controller\Adminhtml\Export;

class Manual extends \Magento\Backend\App\Action
{
	protected $resultPageFactory = false;
	protected $helper;

	public function __construct(
		\Magento\Backend\App\Action\Context $context,
		\ArtM\InvoiceGrid\Helper\Data $helper,
		\Magento\Framework\View\Result\PageFactory $resultPageFactory
	)
	{
		parent::__construct($context);
		$this->helper            = $helper;
		$this->resultPageFactory = $resultPageFactory;
	}

	public function execute()
	{
		$resultPage        = $this->resultPageFactory->create();
		$resultPage->getConfig()->getTitle()->prepend((__('Create Invoices Csv')));
        $invoiceDate       = $this->getRequest()->getPost('invoice_date'); 
		if($invoiceDate){
			$formattedDate = date('Y-m-d', strtotime($invoiceDate));
			$from          = $formattedDate." 00:00:00";
			$to            = $formattedDate." 23:59:59";
			$csvFile       = $this->helper->createCsv($from, $to);
			$resultPage->getLayout()->getBlock('csv.export.manual.page')->setData('csvFile', $csvFile);	
		}
		return $resultPage;
	}
}
?>
