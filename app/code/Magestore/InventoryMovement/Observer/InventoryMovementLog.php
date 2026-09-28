<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magestore\InventoryMovementApi\Api\ProcessCreateMovementsInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;

/**
 * Class InventoryMovementLog
 *
 * Process events and record to database
 */
class InventoryMovementLog implements ObserverInterface
{
    const BUNCH_SIZE = 500;

    /**
     * @var ProcessCreateMovementsInterface
     */
    protected $processCreateMovement;

    /**
     * @var SkipMovementLogInterface
     */
    protected $skipMovementLog;

    /**
     * InventoryMovementLog constructor.
     *
     * @param ProcessCreateMovementsInterface $processCreateMovement
     * @param SkipMovementLogInterface $skipMovementLog
     */
    public function __construct(
        ProcessCreateMovementsInterface $processCreateMovement,
        SkipMovementLogInterface $skipMovementLog
    ) {
        $this->processCreateMovement = $processCreateMovement;
        $this->skipMovementLog = $skipMovementLog;
    }

    /**
     * Observe created
     *
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $movements = $observer->getEvent()->getMovements();
        if ($movements) {
            $chunkBunches = array_chunk($movements, self::BUNCH_SIZE);
            foreach ($chunkBunches as $chunk) {
                $this->processCreateMovement->execute($chunk);
            }
        }
        $this->skipMovementLog->setSkipMovementLog(0);
        return $this;
    }
}
