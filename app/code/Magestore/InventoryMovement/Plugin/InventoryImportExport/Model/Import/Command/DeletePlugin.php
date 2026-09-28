<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\InventoryImportExport\Model\Import\Command;

use Magento\InventoryImportExport\Model\Import\Command\Delete as CoreDelete;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\InventoryImportExport\Model\Import\SourceItemConvert;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovement\Plugin\InventoryImportExport\Plugin\Import\SourceItemImporterPlugin;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\FilterSourceItemInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;
use Psr\Log\LoggerInterface;

/**
 * Class DeletePlugin
 *
 * Plugin for logging import stock sources type "Delete"
 */
class DeletePlugin
{
    /**
     * @var FilterSourceItemInterface
     */
    protected $filterSourceItem;

    /**
     * @var DateTime
     */
    protected $dateTime;

    /**
     * @var EventManager
     */
    protected $eventManager;

    /**
     * @var SkipMovementLogInterface
     */
    protected $skipMovementLog;

    /**
     * @var GetCurrentUserInterface
     */
    protected $getCurrentUser;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var SourceItemConvert
     */
    private $sourceItemConvert;

    /**
     * AppendPlugin constructor.
     *
     * @param FilterSourceItemInterface $filterSourceItem
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetCurrentUserInterface $getCurrentUser
     * @param LoggerInterface $logger
     * @param SourceItemConvert $sourceItemConvert
     */
    public function __construct(
        FilterSourceItemInterface $filterSourceItem,
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetCurrentUserInterface $getCurrentUser,
        LoggerInterface $logger,
        SourceItemConvert $sourceItemConvert
    ) {
        $this->filterSourceItem = $filterSourceItem;
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getCurrentUser = $getCurrentUser;
        $this->logger = $logger;
        $this->sourceItemConvert = $sourceItemConvert;
    }

    /**
     * Around Execute
     *
     * @param CoreDelete $subject
     * @param \Closure $process
     * @param array $bunch
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        CoreDelete $subject,
        \Closure $process,
        array $bunch
    ) {
        if ($this->skipMovementLog->getSkipMovementLog()) {
            /* Process the function */
            $process($bunch);
            return;
        }

        $movements = $this->prepareMovementsBeforeProcess($bunch);
        /* Save log before processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);

        if (count($movements)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }
        /* Process the function */
        $process($bunch);

        /* Save log after processing */
        $movements = $this->prepareMovementsAfterProcess($movements);
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movements]);
    }

    /**
     * Prepare Movements Before Process
     *
     * @param array $bunch
     * @return array
     */
    public function prepareMovementsBeforeProcess(array $bunch)
    {
        $movements = [];

        try {
            $currentUser = $this->getCurrentUser->getCurrentUser();
            $userId = null;
            $userName = null;
            $userType = null;
            if ($currentUser) {
                $userId = $currentUser->getUserId();
                $userType = $currentUser->getUserType();
                $userName = $currentUser->getUserName();
            }

            $sourceItems = $this->sourceItemConvert->convert($bunch);
            $skus = [];
            $sourceCodes = [];

            foreach ($sourceItems as $sourceItem) {
                $skus[] = $sourceItem->getSku();
                $sourceCodes[] = $sourceItem->getSourceCode();
            }

            $oldSourceItems = $this->filterSourceItem->getBySkusAndSourceCodes(
                $skus,
                $sourceCodes
            );

            foreach ($oldSourceItems as $oldSourceItem) {
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $oldSourceItem->getSku(),
                    InventoryMovementInterface::SOURCE_CODE => $oldSourceItem->getSourceCode(),
                    InventoryMovementInterface::CHANGE_QTY => -$oldSourceItem->getQuantity(),
                    InventoryMovementInterface::OLD_QTY => $oldSourceItem->getQuantity(),
                    InventoryMovementInterface::IS_IN_STOCK => 0,
                    InventoryMovementInterface::CREATED_AT => $this->dateTime->gmtDate(),
                    InventoryMovementInterface::ACTION_TYPE => SourceItemImporterPlugin::ACTION_TYPE_IMPORT,
                    InventoryMovementInterface::USER_ID => $userId,
                    InventoryMovementInterface::USER_TYPE => $userType,
                    InventoryMovementInterface::USER_NAME => $userName,
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
                    InventoryMovementInterface::METADATA => [
                        InventoryMovementInterface::ACTION_LABEL => __("Magento Import")
                    ]
                ];
                $movement[InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movement[InventoryMovementInterface::PRODUCT_SKU]
                    . $movement[InventoryMovementInterface::SOURCE_CODE]
                    . $movement[InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();

                $movements[] = $movement;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $movements;
    }

    /**
     * Prepare Movements After Process
     *
     * @param array $movements
     * @return array
     */
    public function prepareMovementsAfterProcess(array $movements)
    {
        foreach (array_keys($movements) as $key) {
            $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            $movements[$key][InventoryMovementInterface::NEW_QTY] = 0;
        }
        return $movements;
    }
}
