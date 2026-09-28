<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer;

/**
 * Class BranchRequest Delivery
 */
class DeliveryProduct extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * ReceiveProduct construct
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_branchrequest_delivery_product', 'delivery_product_id');
    }
}
