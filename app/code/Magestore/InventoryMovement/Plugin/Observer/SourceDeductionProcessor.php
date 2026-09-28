<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Plugin\Observer;

use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Event\Observer;
use Magento\InventoryCatalogApi\Api\DefaultSourceProviderInterface;
use Magento\InventoryCatalogApi\Model\IsSingleSourceModeInterface;
use Magento\InventoryShipping\Model\GetItemsToDeductFromShipment;
use Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory;
use Magento\InventoryShipping\Observer\SourceDeductionProcessor as CoreSourceDeductionProcessor;
use Magento\Sales\Model\Order\Shipment;
use Magestore\InventoryMovement\Model\GetMovementDataFromSourceDeductionRequest;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\SkipMovementLogInterface;

/**
 * Class SourceDeductionProcessor
 *
 * Process events and dispatch event to save movement
 */
class SourceDeductionProcessor
{
    const MOVEMENT_SHIPMENT_TYPE = 'shipment_created';

    /**
     * @var IsSingleSourceModeInterface
     */
    private $isSingleSourceMode;

    /**
     * @var DefaultSourceProviderInterface
     */
    private $defaultSourceProvider;

    /**
     * @var GetItemsToDeductFromShipment
     */
    private $getItemsToDeductFromShipment;

    /**
     * @var SourceDeductionRequestFromShipmentFactory
     */
    private $sourceDeductionRequestFromShipmentFactory;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var GetMovementDataFromSourceDeductionRequest
     */
    private $getMovementDataFromSourceDeductionRequest;

    /**
     * @var SkipMovementLogInterface
     */
    protected $skipMovementLog;

    /**
     * SourceDeductionProcessor constructor.
     *
     * @param IsSingleSourceModeInterface $isSingleSourceMode
     * @param DefaultSourceProviderInterface $defaultSourceProvider
     * @param GetItemsToDeductFromShipment $getItemsToDeductFromShipment
     * @param SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory
     * @param EventManager $eventManager
     * @param GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest
     * @param SkipMovementLogInterface $skipMovementLog
     */
    public function __construct(
        IsSingleSourceModeInterface $isSingleSourceMode,
        DefaultSourceProviderInterface $defaultSourceProvider,
        GetItemsToDeductFromShipment $getItemsToDeductFromShipment,
        SourceDeductionRequestFromShipmentFactory $sourceDeductionRequestFromShipmentFactory,
        EventManager $eventManager,
        GetMovementDataFromSourceDeductionRequest $getMovementDataFromSourceDeductionRequest,
        SkipMovementLogInterface $skipMovementLog
    ) {
        $this->isSingleSourceMode = $isSingleSourceMode;
        $this->defaultSourceProvider = $defaultSourceProvider;
        $this->getItemsToDeductFromShipment = $getItemsToDeductFromShipment;
        $this->sourceDeductionRequestFromShipmentFactory = $sourceDeductionRequestFromShipmentFactory;
        $this->eventManager = $eventManager;
        $this->getMovementDataFromSourceDeductionRequest = $getMovementDataFromSourceDeductionRequest;
        $this->skipMovementLog = $skipMovementLog;
    }

    /**
     * Around deduct qty
     *
     * @param CoreSourceDeductionProcessor $subject
     * @param callable $proceed
     * @param Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundExecute(
        CoreSourceDeductionProcessor $subject,
        callable $proceed,
        Observer $observer
    ) {
        $movementsData = [];
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();

        if ($shipment->getOrigData('entity_id') || $this->skipMovementLog->getSkipMovementLog()) {
            return $proceed($observer);
        }

        $sourceCode = null;
        if (!empty($shipment->getExtensionAttributes())
            && !empty($shipment->getExtensionAttributes()->getSourceCode())) {
            $sourceCode = $shipment->getExtensionAttributes()->getSourceCode();
        } elseif ($this->isSingleSourceMode->execute()) {
            $sourceCode = $this->defaultSourceProvider->getCode();
        }

        $shipmentItems = $this->getItemsToDeductFromShipment->execute($shipment);

        if (!empty($shipmentItems) && $sourceCode) {
            $sourceDeductionRequest = $this->sourceDeductionRequestFromShipmentFactory->execute(
                $shipment,
                $sourceCode,
                $shipmentItems
            );

            $metaData = [
                InventoryMovementInterface::ACTION_LABEL => __('New Shipment For Order No'),
                InventoryMovementInterface::OBJECT_TYPE => 'order',
                InventoryMovementInterface::OBJECT_ID => $shipment->getOrderId(),
                InventoryMovementInterface::OBJECT_INCREMENT_ID => $shipment->getOrder()->getIncrementId()
            ];
            $movementsData = $this->getMovementDataFromSourceDeductionRequest->execute(
                $sourceDeductionRequest,
                $metaData,
                self::MOVEMENT_SHIPMENT_TYPE
            );
            /* Save log before processing */
            $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);
            if (count($movementsData)) {
                $this->eventManager->dispatch('inventory_movement_log_skip_default');
            }
        }

        $result = $proceed($observer);

        $metaData = [
            InventoryMovementInterface::ACTION_LABEL => __('New Shipment No'),
            InventoryMovementInterface::OBJECT_TYPE => 'shipment',
            InventoryMovementInterface::OBJECT_ID => $shipment->getId(),
            InventoryMovementInterface::OBJECT_INCREMENT_ID => $shipment->getIncrementId()
        ];
        $movementsData = $this->getMovementDataFromSourceDeductionRequest->executeAfter(
            $movementsData,
            $metaData
        );
        /* Save log after processing */
        $this->eventManager->dispatch('inventory_movement_log', ['movements' => $movementsData]);

        return $result;
    }
}
