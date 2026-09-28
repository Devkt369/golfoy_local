<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Package;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryShipping\Model\GetItemsToDeductFromShipment;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Sales\Model\Order\ShipmentFactory;
use Magestore\ClickAndCollect\Model\ResourceModel\PackageItem\Collection as PackageItemCollection;
use Magestore\ClickAndCollect\Model\ResourceModel\PackageItem\CollectionFactory as PackageItemCollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\Package\AddItemsToNewPackageInterface;
use Magestore\ClickAndCollectApi\Api\PackageItemRepositoryInterface;

/**
 * Class Save Package
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class AddItemsToNewPackage implements AddItemsToNewPackageInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PackageItemCollectionFactory
     */
    private $packageItemCollectionFactory;

    /**
     * @var ShipmentFactory
     */
    private $shipmentFactory;

    /**
     * @var GetItemsToDeductFromShipment
     */
    private $getItemsToDeductFromShipment;

    /**
     * @var PackageItemInterfaceFactory
     */
    private $packageItemInterfaceFactory;

    /**
     * @var PackageItemRepositoryInterface
     */
    private $packageItemRepository;

    /**
     * AddItemsToNewPackage constructor.
     *
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepositoryInterface $orderRepository
     * @param PackageItemCollectionFactory $packageItemCollectionFactory
     * @param ShipmentFactory $shipmentFactory
     * @param GetItemsToDeductFromShipment $getItemsToDeductFromShipment
     * @param PackageItemInterfaceFactory $packageItemInterfaceFactory
     * @param PackageItemRepositoryInterface $packageItemRepository
     */
    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepositoryInterface $orderRepository,
        PackageItemCollectionFactory $packageItemCollectionFactory,
        ShipmentFactory $shipmentFactory,
        GetItemsToDeductFromShipment $getItemsToDeductFromShipment,
        PackageItemInterfaceFactory $packageItemInterfaceFactory,
        PackageItemRepositoryInterface $packageItemRepository
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->packageItemCollectionFactory = $packageItemCollectionFactory;
        $this->shipmentFactory = $shipmentFactory;
        $this->getItemsToDeductFromShipment = $getItemsToDeductFromShipment;
        $this->packageItemInterfaceFactory = $packageItemInterfaceFactory;
        $this->packageItemRepository = $packageItemRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(PackageInterface $package)
    {
        if (!$this->validatePackageItemsExist($package)) {
            throw new AlreadyExistsException(__('The package has already had items.'));
        }
        try {
            $order = $this->orderRepository->get($package->getOrderId());
            $itemsToShip = $this->getPackageItems($order);
            if (!count($itemsToShip)) {
                return;
            }

            foreach ($itemsToShip as $item) {
                /** @var PackageItemInterface $packageItem */
                $packageItem = $this->packageItemInterfaceFactory->create();
                $packageItem->setData($item);
                $packageItem->setPackageId($package->getPackageId());
                $this->packageItemRepository->save($packageItem);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__('Cannot save items into package.'));
        }
    }

    /**
     * Validate Package Items Exist
     *
     * @param PackageInterface $package
     * @return bool
     */
    public function validatePackageItemsExist(PackageInterface $package)
    {
        /** @var PackageItemCollection $packageItemCollection */
        $packageItemCollection = $this->packageItemCollectionFactory->create();
        $packageItemCollection->addFieldToFilter(PackageItemInterface::PACKAGE_ID, $package->getPackageId());
        if ($packageItemCollection->getSize()) {
            return false;
        }
        return true;
    }

    /**
     * Get Can Ship Items
     *
     * @param OrderInterface $order
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPackageItems(OrderInterface $order)
    {
        $packageItems = [];
        $shipmentItems = [];
        $itemsData = [];
        foreach ($order->getItems() as $item) {
            if (!$item->getIsVirtual()
                && (!$item->getParentItem() || $item->isShipSeparately())
                && $item->getQtyToShip()) {
                $shipmentItems[$item->getItemId()] = $item->getQtyToShip();
            }
            $itemsData[$item->getSku()] = [
                'item_id' => $item->getItemId(),
                'parent_item_id' => $item->getParentItemId(),
                'product_id' => $item->getProductId(),
                'product_sku' => $item->getSku(),
                'product_name' => $item->getName()
            ];
        }
        if (!count($shipmentItems)) {
            return [];
        }

        /** @var Shipment $shipment */
        $shipment = $this->shipmentFactory->create(
            $order,
            $shipmentItems
        );
        $shipmentItemsToDeduct = $this->getItemsToDeductFromShipment->execute($shipment);
        foreach ($shipmentItemsToDeduct as $itemToDeduct) {
            // phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $packageItems[] = array_merge(
                $itemsData[$itemToDeduct->getSku()],
                ['packed_qty' => (float)$itemToDeduct->getQty()]
            );
        }

        return $packageItems;
    }
}
