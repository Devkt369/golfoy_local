<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Core\Plugin\InventoryIndexer\Indexer\Stock;

use Magento\Framework\App\ResourceConnection;
use Magento\InventoryIndexer\Indexer\Stock\ReservationsIndexTable;

/**
 * Core - ReservationsIndexTablePlugin
 */
class ReservationsIndexTablePlugin
{
    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * After Get Table Name, Fix bug index
     *
     * @param ReservationsIndexTable $subject
     * @param string $result
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetTableName(
        ReservationsIndexTable $subject, // phpstan:ignore
        string $result
    ) {
        return $this->resourceConnection->getTableName($result);
    }
}
