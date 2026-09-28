<?php
namespace ArtM\Report\Block;
class Report extends \Magento\Framework\View\Element\Template
{	    
    protected $helperData;
    protected $scopeConfig;
    public function __construct(    
        \Magento\Framework\View\Element\Template\Context $context,
        \ArtM\Report\Helper\Data $helperData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,        
        array $data = []
    ){ 
        $this->helperData = $helperData;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context, $data);
    }
    public function getAllOrders($from, $to){       
        $orders = $this->helperData->getInvoiceDataByDate($from , $to);      
        return $orders;
    }
}
