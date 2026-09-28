<?php

namespace Magestore\ClickAndCollectFrontend\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magestore\ClickAndCollectFrontend\Model\Order\Email\Sender\OrderSender as StoreOwnerSender;
use Psr\Log\LoggerInterface;

/**
 * Class SalesModelServiceQuoteSubmitSuccess
 *
 * Used for send email to store owner
 */
class SalesModelServiceQuoteSubmitSuccess implements ObserverInterface
{
    const TYPE_SEND_NEW_ORDER_TO_STORE_OWNER = 'new_to_store_owner';

    /**
     * @var StoreOwnerSender
     */
    protected $storeOwnerSender;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * SalesModelServiceQuoteSubmitSuccess constructor.
     *
     * @param StoreOwnerSender $storeOwnerSender
     * @param LoggerInterface $logger
     */
    public function __construct(
        StoreOwnerSender $storeOwnerSender,
        LoggerInterface $logger
    ) {
        $this->storeOwnerSender = $storeOwnerSender;
        $this->logger = $logger;
    }

    /**
     * Send email to store owner
     *
     * @param Observer $observer
     * @return bool
     */
    public function execute(Observer $observer)
    {
        try {
            /** @var Order $order */
            $order = $observer->getEvent()->getOrder();
            if ($order->getLocationPickupId()) {
                $this->storeOwnerSender->send($order);
            }
            return true;
        } catch (LocalizedException $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }
}
