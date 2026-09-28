<?php
namespace ArtM\InvoiceGrid\Controller\Cron;
use Magento\Framework\App\Request\Http as HttpRequest;
class Index extends \Magento\Framework\App\Action\Action
{      
    protected $helper;
    protected $pageFactory;
    protected $invoiceGrid;
    protected $request;
    public function __construct(
		\ArtM\InvoiceGrid\Helper\Data $helper,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \ArtM\InvoiceGrid\Model\Invoices $invoiceGrid,
        HttpRequest $request
    )
    {        
		$this->helper      = $helper;
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->invoiceGrid = $invoiceGrid;
        $this->request     = $request;
    }
    
    public function execute()
    {   
		$userAgent = $this->request->getHeader('User-Agent');
        if (strpos($userAgent, 'RunByCurlAgent') === false) {
            echo 'Function not allowed.';
            return;
        }
        $formattedDate = date('Y-m-d');
        $racollection = $this->invoiceGrid->getCollection();
        $racollection->addFieldToFilter('created_at', ['like' => $formattedDate.' ' . '%']);
        $numRecords = $racollection->getSize();
        if ($numRecords == 0) {
            $csvFile = $this->helper->createCsv();
            if($csvFile){
                $this->invoiceGrid->setData([
                    "invoice" => $csvFile
                ]);
                $this->invoiceGrid->save();
            }
        }
        
        
        $page = $this->pageFactory->create();
        return $page;
    }        
}




