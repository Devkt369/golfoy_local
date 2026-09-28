<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\Catalog\Controller\Adminhtml\Product;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Catalog\Controller\Adminhtml\Product\Save as SaveCore;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magestore\Core\Api\GetCurrentUserInterface;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;

/**
 * Class SavePlugin
 *
 * Plugin for log save product
 */
class SavePlugin
{
    const NEW_QTY_THEORY = 'new_qty_theory';
    const ACTION_TYPE_MANUALLY_CHANGE = 'manually_change';

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
     * @var GetSourceItemsBySkuInterface
     */
    protected $getSourceItemsBySku;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var GetCurrentUserInterface
     */
    private $getCurrentUser;

    /**
     * Default Source Provider
     *
     * @var DefaultSourceProviderInterface $defaultSource
     */
    protected $defaultSource;

    /**
     * @var IsSingleSourceModeInterface
     */
    protected $isSingleSourceMode;

    /**
     * SavePlugin constructor.
     * @param DateTime $dateTime
     * @param EventManager $eventManager
     * @param SkipMovementLogInterface $skipMovementLog
     * @param GetSourceItemsBySkuInterface $getSourceItemsBySku
     * @param DataPersistorInterface $dataPersistor
     * @param GetCurrentUserInterface $getCurrentUser
     * @param DefaultSourceProviderInterface $defaultSource
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     */
    public function __construct(
        DateTime $dateTime,
        EventManager $eventManager,
        SkipMovementLogInterface $skipMovementLog,
        GetSourceItemsBySkuInterface $getSourceItemsBySku,
        DataPersistorInterface $dataPersistor,
        GetCurrentUserInterface $getCurrentUser,
        DefaultSourceProviderInterface $defaultSource,
        IsSingleSourceModeInterface $isSingleSourceMode
    ) {
        $this->dateTime = $dateTime;
        $this->eventManager = $eventManager;
        $this->skipMovementLog = $skipMovementLog;
        $this->getSourceItemsBySku = $getSourceItemsBySku;
        $this->dataPersistor = $dataPersistor;
        $this->getCurrentUser = $getCurrentUser;
        $this->defaultSource = $defaultSource;
        $this->isSingleSourceMode = $isSingleSourceMode;
    }

    /**
     * Save movement log
     *
     * @param SaveCore $subject
     * @param \Closure $process
     * @return mixed
     */
    public function aroundExecute(
        SaveCore $subject,
        \Closure $process
    ) {
        $data = $subject->getRequest()->getPostValue();

        if (!$data || $this->skipMovementLog->getSkipMovementLog()) {
            return $process();
        }

        $productSku = $data['product']['sku'];
        $movements = $this->prepareMovementsBeforeProcess($data);

        $movementsDataLogs = $this->prepareMovementsDataLogs($productSku, $movements);
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsDataLogs]);

        if (count($movementsDataLogs)) {
            $this->eventManager->dispatch('inventory_movement_log_skip_default');
        }

        /* Process the function */
        $result = $process();

        // Skip log if save product failed
        if ($this->dataPersistor->get('catalog_product')) {
            $this->eventManager->dispatch('inventory_movement_log_disable_skip_log');
            return $result;
        }

        $movementsDataLogs = $this->prepareMovementsDataLogs($productSku, $movements, true);
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsDataLogs]);

        return $result;
    }

    /**
     * Prepare Movements Before Process
     *
     * @param array|null $data
     * @return array
     */
    public function prepareMovementsBeforeProcess(?array $data = null): array
    {
        $movements = [];

        if (!$data) {
            return $movements;
        }
        $productSku = $data['product']['sku'];

        if ($this->isSingleSourceMode->execute()) {
            if (!isset($data['product']['quantity_and_stock_status']['qty'])) {
                return $movements;
            }
            $qty = (float) $data['product']['quantity_and_stock_status']['qty'];
            $defaultSourceCode = $this->defaultSource->getCode();
            $movements[$defaultSourceCode] = [
                InventoryMovementInterface::PRODUCT_SKU => $productSku,
                InventoryMovementInterface::SOURCE_CODE => $this->defaultSource->getCode(),
                InventoryMovementInterface::OLD_QTY => 0,
                // Default set change qty = new qty (for save new source item)
                InventoryMovementInterface::CHANGE_QTY => $qty,
                InventoryMovementInterface::NEW_QTY => null,
                InventoryMovementInterface::IS_IN_STOCK => $data['product']['quantity_and_stock_status']['is_in_stock'],
                self::NEW_QTY_THEORY => $qty,
                InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL,
            ];

            $movements[$defaultSourceCode][InventoryMovementInterface::STATUS] =
                InventoryMovementInterface::STATUS_FAIL;
            $movements[$defaultSourceCode][InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                $movements[$defaultSourceCode][InventoryMovementInterface::PRODUCT_SKU]
                . $movements[$defaultSourceCode][InventoryMovementInterface::SOURCE_CODE]
                . $movements[$defaultSourceCode][InventoryMovementInterface::CHANGE_QTY]
                . $this->dateTime->timestamp();
        } else {
            $assignedSources =
                isset($data['sources']['assigned_sources'])
                && is_array($data['sources']['assigned_sources'])
                    ? $data['sources']['assigned_sources']
                    : [];

            foreach ($assignedSources as $source) {
                $sourceCode = $source['source_code'];
                $movements[$sourceCode] = [
                    InventoryMovementInterface::PRODUCT_SKU => $productSku,
                    InventoryMovementInterface::SOURCE_CODE => $source['source_code'],
                    // Default set change qty = new qty (for save new source item)
                    InventoryMovementInterface::CHANGE_QTY => (float) $source['quantity'],
                    InventoryMovementInterface::NEW_QTY => null,
                    InventoryMovementInterface::IS_IN_STOCK => $source['status'],
                    InventoryMovementInterface::OLD_QTY => 0,
                    self::NEW_QTY_THEORY => (float) $source['quantity'],
                    InventoryMovementInterface::STATUS => InventoryMovementInterface::STATUS_FAIL
                ];

                $movements[$sourceCode][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_FAIL;
                $movements[$sourceCode][InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                    $movements[$sourceCode][InventoryMovementInterface::PRODUCT_SKU]
                    . $movements[$sourceCode][InventoryMovementInterface::SOURCE_CODE]
                    . $movements[$sourceCode][InventoryMovementInterface::CHANGE_QTY]
                    . $this->dateTime->timestamp();
            }
        }

        $oldAssignedSources = $this->getSourceItemsBySku->execute($productSku);
        foreach ($oldAssignedSources as $oldAssignedSource) {
            $sourceCode = $oldAssignedSource->getSourceCode();
            if (isset($movements[$sourceCode])) {
                $movements[$sourceCode][InventoryMovementInterface::CHANGE_QTY] =
                    $movements[$sourceCode][self::NEW_QTY_THEORY] - $oldAssignedSource->getQuantity();
            } else {
                $movements[$sourceCode] = [
                    InventoryMovementInterface::PRODUCT_SKU => $productSku,
                    InventoryMovementInterface::SOURCE_CODE => $sourceCode,
                    // Default set change qty = -quantity (unassign source)
                    InventoryMovementInterface::CHANGE_QTY => -$oldAssignedSource->getQuantity(),
                    InventoryMovementInterface::NEW_QTY => 0,
                    InventoryMovementInterface::IS_IN_STOCK => 0,
                ];
            }
            $movements[$sourceCode][InventoryMovementInterface::OLD_QTY] = $oldAssignedSource->getQuantity();

            $movements[$sourceCode][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_FAIL;
            $movements[$sourceCode][InventoryMovementInterface::INVENTORY_MOVEMENT_ID] =
                $movements[$sourceCode][InventoryMovementInterface::PRODUCT_SKU]
                . $movements[$sourceCode][InventoryMovementInterface::SOURCE_CODE]
                . $movements[$sourceCode][InventoryMovementInterface::CHANGE_QTY]
                . $this->dateTime->timestamp();
        }

        return $movements;
    }

    /**
     * Prepare Movements Data Logs
     *
     * @param string $productSku
     * @param array $movements
     * @param bool $isAfter
     * @return array
     */
    public function prepareMovementsDataLogs(string $productSku, array $movements, bool $isAfter = false)
    {
        $movementsDataLogs = [];
        if ($isAfter) {
            /* Get source item by sku */
            $sourceCollection = $this->getSourceItemsBySku->execute($productSku);

            foreach ($sourceCollection as $source) {
                if (isset($movements[$source->getSourceCode()])) {
                    $movements[$source->getSourceCode()][InventoryMovementInterface::NEW_QTY]
                        = $source->getQuantity();
                    $movements[$source->getSourceCode()][InventoryMovementInterface::IS_IN_STOCK]
                        = $source->getStatus();
                }
            }
        }

        $currentUser = $this->getCurrentUser->getCurrentUser();

        foreach ($movements as $movement) {
            if ($isAfter) {
                $movement[InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            } else {
                unset($movement[InventoryMovementInterface::NEW_QTY]);
            }
            $movement[InventoryMovementInterface::CREATED_AT] = $this->dateTime->gmtDate();
            $movement[InventoryMovementInterface::ACTION_TYPE] = self::ACTION_TYPE_MANUALLY_CHANGE;
            if ($currentUser) {
                $movement[InventoryMovementInterface::USER_ID] = $currentUser->getUserId();
                $movement[InventoryMovementInterface::USER_NAME] = $currentUser->getUserName();
                $movement[InventoryMovementInterface::USER_TYPE] = $currentUser->getUserType();
            }
            $movement[InventoryMovementInterface::METADATA] = [
                InventoryMovementInterface::ACTION_LABEL => __('Manually Change')
            ];
            unset($movement[self::NEW_QTY_THEORY]);
            if ((float) $movement[InventoryMovementInterface::CHANGE_QTY]) {
                $movementsDataLogs[] = $movement;
            }
        }
        return $movementsDataLogs;
    }
}
