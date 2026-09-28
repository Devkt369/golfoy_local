<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\ClickAndCollect\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface get Order Pickup Status
 *
 * @api
 */
interface OrderPickupStatusResolverInterface
{
    const STATUS_PENDING = 'pending';
    const STATUS_READY_TO_PICK = 'ready_to_pick';
    const STATUS_DELIVERED = 'delivered';

    /**
     * Get PickupStatus For Order
     *
     * @param OrderInterface $order
     * @return string|null
     */
    public function getPickupStatusForOrder(OrderInterface $order);

    /**
     * Get PickupStatus For Order By Order Id
     *
     * @param int $orderId
     * @return string|null
     */
    public function getPickupStatusForOrderByOrderId(int $orderId);
}
