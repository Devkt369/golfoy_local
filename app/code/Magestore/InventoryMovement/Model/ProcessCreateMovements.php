<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Framework\Serialize\Serializer\Json as JsonSerialize;
use Magestore\InventoryMovement\Model\ResourceModel\InventoryMovement as InventoryMovementResource;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\GetProductInfoForMovementInterface;
use Magestore\InventoryMovementApi\Api\GetSourceInfoForMovementInterface;
use Magestore\InventoryMovementApi\Api\ProcessCreateMovementsInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\Filter\FilterManager;

/**
 * Service for save movements to interface
 *
 * @api
 */
class ProcessCreateMovements implements ProcessCreateMovementsInterface
{
    /**
     * @var GetSourceInfoForMovementInterface
     */
    protected $getSourceInfoForMovement;

    /**
     * @var GetProductInfoForMovementInterface
     */
    protected $getProductInfoForMovement;

    /**
     * @var InventoryMovementResource
     */
    protected $inventoryMovementResource;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var JsonSerialize
     */
    protected $jsonSerialize;

    /**
     * @var FilterManager
     */
    protected $filter;

    /**
     * ProcessCreateMovements constructor.
     *
     * @param GetSourceInfoForMovementInterface $getSourceInfoForMovement
     * @param GetProductInfoForMovementInterface $getProductInfoForMovement
     * @param InventoryMovementResource $inventoryMovementResource
     * @param JsonSerialize $jsonSerialize
     * @param LoggerInterface $logger
     * @param FilterManager $filter
     */
    public function __construct(
        GetSourceInfoForMovementInterface $getSourceInfoForMovement,
        GetProductInfoForMovementInterface $getProductInfoForMovement,
        InventoryMovementResource $inventoryMovementResource,
        JsonSerialize $jsonSerialize,
        LoggerInterface $logger,
        FilterManager $filter
    ) {
        $this->getSourceInfoForMovement = $getSourceInfoForMovement;
        $this->getProductInfoForMovement = $getProductInfoForMovement;
        $this->inventoryMovementResource = $inventoryMovementResource;
        $this->logger = $logger;
        $this->jsonSerialize = $jsonSerialize;
        $this->filter = $filter;
    }

    /**
     * Execute service
     *
     * @param array $movements
     * @return void
     */
    public function execute(array $movements): void
    {
        /* One query to get bulk product info */
        $productInfo = $this->getProductInfoForMovement->execute($movements);
        /* One query to get bulk source info */
        $sourceInfo = $this->getSourceInfoForMovement->execute($movements);

        $productNames = $productInfo['product_names'];
        $productIds = $productInfo['product_ids'];

        $sourceNames = $sourceInfo['source_names'];

        foreach ($movements as &$movementData) {
            if (isset($productNames[$movementData[InventoryMovementInterface::PRODUCT_SKU]])) {
                $movementData[InventoryMovementInterface::PRODUCT_NAME]
                    = $productNames[$movementData[InventoryMovementInterface::PRODUCT_SKU]] ?? '';
            }
            if (isset($productIds[$movementData[InventoryMovementInterface::PRODUCT_SKU]])) {
                $movementData[InventoryMovementInterface::PRODUCT_ID]
                    = $productIds[$movementData[InventoryMovementInterface::PRODUCT_SKU]] ?? 0;
            }
            if (isset($sourceNames[$movementData[InventoryMovementInterface::SOURCE_CODE]])) {
                $movementData[InventoryMovementInterface::SOURCE_NAME]
                    = $sourceNames[$movementData[InventoryMovementInterface::SOURCE_CODE]] ?? '';
            }

            if (isset($movementData[InventoryMovementInterface::METADATA])) {
                $metaData = $movementData[InventoryMovementInterface::METADATA];
                $movementData[InventoryMovementInterface::METADATA] = $this->jsonSerialize->serialize($metaData);
                $movementData[InventoryMovementInterface::NOTE] = $this->convertMetaDataToNote($metaData);
            }
        }

        try {
            /* Bulk insert */
            $this->inventoryMovementResource->getConnection()->insertOnDuplicate(
                $this->inventoryMovementResource->getMainTable(),
                $movements
            );
        } catch (\Exception $exception) {
            $this->logger->debug($exception->getMessage());
        }
    }

    /**
     * Convert metadata to note
     *
     * @param array $metadata
     * @return string
     */
    private function convertMetaDataToNote(array $metadata): string
    {
        $note = "";
        if (isset($metadata[InventoryMovementInterface::ACTION_LABEL])) {
            $note .= $metadata[InventoryMovementInterface::ACTION_LABEL];
        }
        if (isset($metadata[InventoryMovementInterface::OBJECT_INCREMENT_ID])) {
            $note .= ': #' . $metadata[InventoryMovementInterface::OBJECT_INCREMENT_ID];
        }
        return $this->filter->stripTags($note);
    }
}
