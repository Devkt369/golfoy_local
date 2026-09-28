<?php
namespace ArtM\Delhiveryqc\Block\Adminhtml\Delhivery;

use Magento\Framework\View\Element\Template;

class Delhivery extends Template
{
    protected $connection;
    private $OrderData;

    public function __construct(
        Template\Context $context,
        \ArtM\Delhiveryqc\Model\OrderQcFactory $OrderData,
        \ArtM\Delhiveryqc\Model\ProductQcFactory $ProductQc,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->OrderQc = $OrderData;
        $this->ProductQc = $ProductQc;
        
    }

    public function getDelhiveryQcOrders($pagesize=10 , $curpage=1)
    {
        $oData = $this->OrderQc->Create();
        $orderCollection = $oData->getCollection()
        ->setPageSize($pagesize) // only get 10 products 
        ->setCurPage($curpage);
        return $orderCollection;
    }
    
    public function getProductData($id){
        $PData = $this->ProductQc->Create();
        $ProductCollection = $PData
            ->getCollection()
            ->addFieldToFilter('qc_order_table_primary_id', $id);
        return $ProductCollection;
    }
    
}
