<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Order;

/**
 * Check order has enough qty to pick in location or not
 *
 * @api
 */
interface IsEnoughQtyInStockInterface
{
    /**
     * Check order include out of stock or not
     *
     * @param \Magento\Sales\Model\Order|\Magento\Sales\Api\Data\OrderInterface $order
     * @param \Magestore\Webpos\Api\Data\Location\LocationInterface $store
     * @return bool
     */
    public function execute(
        $order,
        \Magestore\Webpos\Api\Data\Location\LocationInterface $store
    ): bool;
}
