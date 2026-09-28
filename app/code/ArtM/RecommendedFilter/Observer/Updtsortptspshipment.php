<?php
namespace ArtM\RecommendedFilter\Observer;

use Magento\Framework\Event\ObserverInterface;
use ArtM\RecommendedFilter\Helper\Data;
use ArtM\RecommendedSort\Helper\Data as SortDataHelper;

class Updtsortptspshipment implements ObserverInterface
{
    protected $helper;
    protected $sortDataHelper;

    public function __construct(
        Data $helper, 
        SortDataHelper $sortDataHelper
    ) {
        $this->helper = $helper;
        $this->sortDataHelper = $sortDataHelper;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {   
        $shipment = $observer->getEvent()->getShipment();
        $items = $shipment->getAllItems();

        foreach ($items as $item) {
            $this->sortDataHelper->addProductToQueue($item->getProductId(), 'shipment-order');
        }
    }
}