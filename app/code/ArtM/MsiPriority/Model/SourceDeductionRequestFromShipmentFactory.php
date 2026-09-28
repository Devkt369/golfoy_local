<?php

declare(strict_types=1);

namespace ArtM\MsiPriority\Model;

use Magento\InventoryShipping\Model\SourceDeductionRequestFromShipmentFactory as CoreFactory;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\InventorySalesApi\Api\Data\SalesEventInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterfaceFactory;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionFactory;
use Magento\InventorySalesApi\Api\Data\SalesEventExtensionInterface;
use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequestInterfaceFactory;
use Magento\Store\Api\WebsiteRepositoryInterface;

class SourceDeductionRequestFromShipmentFactory extends CoreFactory
{
    /**
     * @var SourceDeductionRequestInterfaceFactory
     */
    private $sourceDeductionRequestFactory;

    private $salesChannelFactory;
    private $salesEventFactory;
    private $websiteRepository;
    private $salesEventExtensionFactory;

    public function __construct(
        SourceDeductionRequestInterfaceFactory $sourceDeductionRequestFactory,
        SalesChannelInterfaceFactory $salesChannelFactory,
        SalesEventInterfaceFactory $salesEventFactory,
        WebsiteRepositoryInterface $websiteRepository,
        SalesEventExtensionFactory $salesEventExtensionFactory
    ) {
        parent::__construct(
            $sourceDeductionRequestFactory,
            $salesChannelFactory,
            $salesEventFactory,
            $websiteRepository,
            $salesEventExtensionFactory
        );

        $this->sourceDeductionRequestFactory = $sourceDeductionRequestFactory;
        $this->salesChannelFactory = $salesChannelFactory;
        $this->salesEventFactory = $salesEventFactory;
        $this->websiteRepository = $websiteRepository;
        $this->salesEventExtensionFactory = $salesEventExtensionFactory;
    }

    public function execute(
        Shipment $shipment,
        string $sourceCode,
        array $items
    ): SourceDeductionRequestInterface {
        $websiteId = $shipment->getOrder()->getStore()->getWebsiteId();
        $extAttrs = $shipment->getExtensionAttributes();
        $vinForceShipment = $extAttrs?->getVinForceShipment();
        $orderId = $shipment->getOrder()->getIncrementId();

        $salesEventExtension = $this->salesEventExtensionFactory->create([
            'data' => ['objectIncrementId' => (string)$shipment->getOrder()->getIncrementId()]
        ]);

        $salesEvent = $this->salesEventFactory->create([
            'type' => SalesEventInterface::EVENT_SHIPMENT_CREATED,
            'objectType' => SalesEventInterface::OBJECT_TYPE_ORDER,
            'objectId' => (string)$shipment->getOrderId()
        ]);
        $salesEvent->setExtensionAttributes($salesEventExtension);

        $websiteCode = $this->websiteRepository->getById($websiteId)->getCode();
        $salesChannel = $this->salesChannelFactory->create([
            'data' => [
                'type' => SalesChannelInterface::TYPE_WEBSITE,
                'code' => $websiteCode
            ]
        ]);

        /** @var \ArtM\MsiPriority\Model\SourceDeductionRequest $sourceDeductionRequest */
        $sourceDeductionRequest = $this->sourceDeductionRequestFactory->create([
            'sourceCode' => $sourceCode,
            'items' => $items,
            'salesChannel' => $salesChannel,
            'salesEvent' => $salesEvent,
        ]);

        if ($sourceDeductionRequest instanceof \ArtM\MsiPriority\Model\SourceDeductionRequest) {
            if ($vinForceShipment !== null) {
                $sourceDeductionRequest->setVinForceShipment((bool)$vinForceShipment);
            }
            $sourceDeductionRequest->setOrderId((string)$orderId);
        }

        return $sourceDeductionRequest;
    }
}
