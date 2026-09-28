<?php

namespace Magestore\BranchRequest\Model\InventoryTransfer;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferProductInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Class BranchRequest InventoryTransferProduct
 */
class InventoryTransferProduct extends AbstractModel implements InventoryTransferProductInterface
{
    /**
     * InventoryTransferProduct construct
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init(
            \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct::class
        );
    }
}
