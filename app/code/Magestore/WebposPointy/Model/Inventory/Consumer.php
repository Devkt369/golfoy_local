<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model\Inventory;

use Magento\Framework\MessageQueue\PublisherInterface;
use Magestore\WebposPointy\Api\PointyManagementInterface;
use Magestore\WebposPointy\Log\Logger;

/**
 * Consumer message queue for sync inventory
 */
class Consumer
{
    const QUEUE_NAME = 'magestore_pointy_integration.submit_inventory.queue';
    const QUEUE_TOPIC_NAME = 'magestore_pointy_integration.submit_inventory.topic';
    const QUEUE_CONSUMER_NAME = 'magestore_pointy_integration.submit_inventory.consumer';

    /**
     * @var PublisherInterface
     */
    protected $publisher;

    /**
     * @var PointyManagementInterface
     */
    protected $pointyManagement;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Consumer constructor.
     * @param PublisherInterface $publisher
     * @param PointyManagementInterface $pointyManagement
     * @param Logger $logger
     */
    public function __construct(
        PublisherInterface $publisher,
        PointyManagementInterface $pointyManagement,
        Logger $logger
    ) {
        $this->publisher = $publisher;
        $this->pointyManagement = $pointyManagement;
        $this->logger = $logger;
    }

    /**
     * Execute
     *
     * @param int $locationId
     */
    public function execute(int $locationId)
    {
        try {
            $this->pointyManagement->submitInventory($locationId);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
