<?php

namespace ArtM\Analysis\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order\Item;

class SalesOrderItemSaveBefore implements ObserverInterface
{
    public function execute(Observer $observer)
    {     
        $orderItem = $observer->getEvent()->getItem();
        $product = $orderItem->getProduct();
        $yesNo = ($product->getClearanceSale()) ? 'Yes' : 'No';
        if($yesNo == 'Yes'){
            $orderItem->setData('on_clearance', 1);
        }        
    }
}
?>