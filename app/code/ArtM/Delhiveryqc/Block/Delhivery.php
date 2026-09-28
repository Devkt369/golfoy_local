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
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->OrderQc = $OrderData;
    }

    public function getDelhiveryQcOrders()
    {
        $oData = $this->OrderQc->Create();
        $orderCollection = $oData->getCollection();
        return $orderCollection;
    }
}
