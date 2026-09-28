<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\InventoryImportExport\Model\Import\Command;

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
 * Class ImportPluginAbstract
 *
 * Abstract for plugin import stock sources
 */
abstract class ImportPluginAbstract
{
    const SKU_KEY = 'SKU';
    const SOURCE_CODE_KEY = "SOURCE_CODE";
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
     * Prepare Movements Before Process
     *
     * @param array $bunch
     * @return array[]
     */
    public function prepareMovementsBeforeProcess(array $bunch)
    {
        $movements = [];
        $newQtyData = [];

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
                $movement = [
                    InventoryMovementInterface::PRODUCT_SKU => $sourceItem->getSku(),
                    InventoryMovementInterface::SOURCE_CODE => $sourceItem->getSourceCode(),
                    InventoryMovementInterface::CHANGE_QTY => $sourceItem->getQuantity(),
                    InventoryMovementInterface::OLD_QTY => 0,
                    InventoryMovementInterface::IS_IN_STOCK => $sourceItem->getStatus(),
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

                $key = self::SKU_KEY . $sourceItem->getSku() . self::SOURCE_CODE_KEY . $sourceItem->getSourceCode();
                $movements[$key] = $movement;
                $newQtyData[$key] = $sourceItem->getQuantity();
            }

            $oldSourceItems = $this->filterSourceItem->getBySkusAndSourceCodes(
                $skus,
                $sourceCodes
            );

            foreach ($oldSourceItems as $oldSourceItem) {
                $key = self::SKU_KEY . $oldSourceItem->getSku()
                    . self::SOURCE_CODE_KEY . $oldSourceItem->getSourceCode();
                if (isset($movements[$key])) {
                    $changeQty = $newQtyData[$key] - $oldSourceItem->getQuantity();
                    if (!$changeQty) {
                        unset($movements[$key]);
                        unset($newQtyData[$key]);
                    } else {
                        $movements[$key][InventoryMovementInterface::CHANGE_QTY] = $changeQty;
                        $movements[$key][InventoryMovementInterface::OLD_QTY] = $oldSourceItem->getQuantity();
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return [
            'movements' => $movements,
            'newQtyData' => $newQtyData
        ];
    }

    /**
     * Prepare Movements After Process
     *
     * @param array $movementsData
     * @return array
     */
    public function prepareMovementsAfterProcess(array $movementsData)
    {
        $movements = isset($movementsData['movements']) ? $movementsData['movements'] : [];
        foreach (array_keys($movements) as $key) {
            $movements[$key][InventoryMovementInterface::STATUS] = InventoryMovementInterface::STATUS_SUCCESS;
            $movements[$key][InventoryMovementInterface::NEW_QTY] = $movementsData['newQtyData'][$key];
        }
        return $movements;
    }
}
