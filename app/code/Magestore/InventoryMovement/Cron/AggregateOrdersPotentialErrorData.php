<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Cron;

use Magestore\InventoryMovement\Service\PotentialErrorOrders\PotentialErrorOrdersService;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class AggregateOrdersPotentialErrorData
 *
 * Used to aggregate orders potential error data
 */
class AggregateOrdersPotentialErrorData
{
    /**
     * @var PotentialErrorOrdersService
     */
    protected $potentialErrorOrdersService;

    /**
     * AggregateOrdersPotentialErrorData constructor.
     *
     * @param PotentialErrorOrdersService $potentialErrorOrdersService
     */
    public function __construct(
        PotentialErrorOrdersService $potentialErrorOrdersService
    ) {
        $this->potentialErrorOrdersService = $potentialErrorOrdersService;
    }

    /**
     * Refresh orders potential error data
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->potentialErrorOrdersService->aggregate();
    }
}
