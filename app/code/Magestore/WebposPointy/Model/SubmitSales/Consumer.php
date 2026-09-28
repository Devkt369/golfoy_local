<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model\SubmitSales;

use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\WebposPointy\Api\PointyManagementInterface;
use Magestore\WebposPointy\Log\Logger;

/**
 * Consumer message queue for submit sales to Pointy
 */
class Consumer
{
    const QUEUE_NAME = 'magestore_pointy_integration.submit_sales.queue';
    const QUEUE_TOPIC_NAME = 'magestore_pointy_integration.submit_sales.topic';
    const QUEUE_CONSUMER_NAME = 'magestore_pointy_integration.submit_sales.consumer';

    /**
     * @var LocationRepositoryInterface
     */
    private $posOrderRepository;

    /**
     * @var PointyManagementInterface
     */
    private $pointyManagement;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Consumer constructor.
     * @param LocationRepositoryInterface $posOrderRepository
     * @param PointyManagementInterface $pointyManagement
     * @param Logger $logger
     */
    public function __construct(
        LocationRepositoryInterface $posOrderRepository,
        PointyManagementInterface $pointyManagement,
        Logger $logger
    ) {
        $this->posOrderRepository = $posOrderRepository;
        $this->pointyManagement = $pointyManagement;
        $this->logger = $logger;
    }

    /**
     * Process submit sales
     *
     * @param int $locationId
     * @throws \Exception
     */
    public function execute(int $locationId)
    {
        try {
            $this->pointyManagement->submitSales($locationId);
        } catch (\Exception $e) {
            $this->logger->info($e->getMessage());
        }
    }
}
