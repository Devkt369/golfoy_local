<?php

namespace ArtM\SFMC\Observer\Sales;
use ArtM\SFMC\Model\SfmcOrderSyncSucceedFactory;
use ArtM\SFMC\Model\SfmcOrderFailedFactory;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderSaveAfter implements ObserverInterface
{
    protected $orderSyncSucceedFactory;
    protected $sfmcOrderFailedFactory;
    protected $orderLogger;
    protected $orderRepository;
    
    public function __construct(
        SfmcOrderSyncSucceedFactory $orderSyncSucceedFactory,
        SfmcOrderFailedFactory $sfmcOrderFailedFactory,
        OrderRepositoryInterface $orderRepository,
        \ArtM\SFMC\Logger\OrderLogger $orderLogger,
    ){
        $this->sfmcOrderFailedFactory = $sfmcOrderFailedFactory;
        $this->orderSyncSucceedFactory = $orderSyncSucceedFactory;
        $this->orderRepository = $orderRepository;
        $this->orderLogger = $orderLogger;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        $orderId = $order->getId();
        $orderSyncSucceedModel = $this->orderSyncSucceedFactory->create();
        $orderSyncSucceedModel = $orderSyncSucceedModel->getCollection()->addFieldToFilter('order_id' , ['eq' => $orderId]);
        foreach($orderSyncSucceedModel as $orderSyncSucceedDelete){
            $orderSyncSucceedDelete->delete();
        }
        $this->orderLogger->info('Order id has deleted from success table to sync again - '.$orderId);

        $sfmcOrderFailedFactory = $this->sfmcOrderFailedFactory->create();
        $sfmcOrderFailedFactory = $sfmcOrderFailedFactory->getCollection()->addFieldToFilter('order_id' , ['eq' => $orderId]);
        foreach($sfmcOrderFailedFactory as $sfmcOrderFailedDelete){
            $sfmcOrderFailedDelete->delete();
        }
        $this->orderLogger->info('Order id has deleted from failed table to sync again - '.$orderId);
        return true;
    }
}
