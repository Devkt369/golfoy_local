<?php

namespace ArtM\Common\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
//use Magento\Sales\Model\Order;
use Magento\Backend\Model\Auth\Session;

class SalesOrderCancelAfter implements ObserverInterface
{

    protected $authSession;

    public function __construct(
        Session $authSession
    )
    {
        $this->authSession = $authSession;
    }

    public function execute(Observer $observer)
    {
        $userName = $this->authSession->getUser()->getUsername();;
        $order = $observer->getEvent()->getOrder(); 
        $order->addCommentToStatusHistory("$userName cancelled order"); 
    }
}