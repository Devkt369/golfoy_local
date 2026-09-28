<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model\ResourceModel;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\ObjectManagerInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magento\Framework\App\ResourceConnection;

/**
 * Class InventoryMovement
 *
 * Used for InventoryMovement resource model
 */
class InventoryMovement extends AbstractDb
{
    /**
     * Inventory movement connection  in this resource model
     *
     * @var AdapterInterface
     */
    protected $inventoryConnection;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param Context $context
     * @param string $connectionName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Context $context,
        $connectionName = null
    ) {
        $this->objectManager = $objectManager;
        parent::__construct($context, $connectionName);
    }
    /**
     * Resource Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_inventory_movement', InventoryMovementInterface::INVENTORY_MOVEMENT_ID);
    }

    /**
     * Get connection
     *
     * @return AdapterInterface|false
     */
    public function getConnection()
    {
        if (!$this->inventoryConnection) {
            $this->inventoryConnection =  $this->objectManager->create(ResourceConnection::class)->getConnection();
        }
        return $this->inventoryConnection;
    }
}
