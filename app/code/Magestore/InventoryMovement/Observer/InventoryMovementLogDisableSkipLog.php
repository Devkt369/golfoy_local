<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;

/**
 * Set skip movement log is 0
 */
class InventoryMovementLogDisableSkipLog implements ObserverInterface
{
    /**
     * @var SkipMovementLogInterface
     */
    protected $skipMovementLog;

    /**
     * InventoryMovementLogSkipDefault constructor.
     *
     * @param SkipMovementLogInterface $skipMovementLog
     */
    public function __construct(
        SkipMovementLogInterface $skipMovementLog
    ) {
        $this->skipMovementLog = $skipMovementLog;
    }

    /**
     * Observe created
     *
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(Observer $observer)
    {
        $this->skipMovementLog->setSkipMovementLog(0);
    }
}
