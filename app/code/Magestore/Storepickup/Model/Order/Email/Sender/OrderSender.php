<?php
namespace Magestore\Storepickup\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magestore\Storepickup\Service\IsStorePickupOrder;

/**
 * Class OrderSender used for plugin before send email
 */
class OrderSender
{
    /**
     * @var bool
     */
    private $isStorePickupOrder;

    /**
     * OrderSender constructor.
     *
     * @param IsStorePickupOrder $isStorePickupOrder
     */
    public function __construct(
        IsStorePickupOrder $isStorePickupOrder
    ) {
        $this->isStorePickupOrder = $isStorePickupOrder;
    }

    /**
     * Function beforeSend
     *
     * @param Order\Email\Sender\OrderSender $orderSender
     * @param Order $order
     * @param bool $forceSyncMode
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSend(
        Order\Email\Sender\OrderSender $orderSender,
        Order $order,
        $forceSyncMode = false
    ) {
        if ($order->getStorepickupId()) {
            $this->isStorePickupOrder->setIsStorePickUpOrder(true);
        }
        return [$order, $forceSyncMode];
    }
}
