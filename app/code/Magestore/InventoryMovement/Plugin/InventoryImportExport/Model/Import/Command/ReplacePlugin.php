<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\InventoryImportExport\Model\Import\Command;

use Magento\InventoryImportExport\Model\Import\Command\Replace as CoreReplace;

/**
 * Class ReplacePlugin
 *
 * Plugin for logging import stock sources type "Replace"
 */
class ReplacePlugin extends ImportPluginAbstract
{
    /**
     * Around Execute
     *
     * @param CoreReplace $subject
     * @param \Closure $process
     * @param array $bunch
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        CoreReplace $subject,
        \Closure $process,
        array $bunch
    ) {
        if ($this->skipMovementLog->getSkipMovementLog()) {
            /* Process the function */
            $process($bunch);
            return;
        }

        $movementsData = $this->prepareMovementsBeforeProcess($bunch);
        $movements = $movementsData['movements'];
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        if (count($movements)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }
        /* Process the function */
        $process($bunch);

        /* Save log after processing */
        $movements = $this->prepareMovementsAfterProcess($movementsData);
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
    }
}
