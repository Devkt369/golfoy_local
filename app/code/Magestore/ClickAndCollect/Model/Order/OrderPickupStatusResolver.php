<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\ClickAndCollect\Model\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magestore\ClickAndCollect\Model\Carrier\Method;
use Magestore\ClickAndCollect\Model\ResourceModel\Package\Collection as PackageCollection;
use Magestore\ClickAndCollect\Model\ResourceModel\Package\CollectionFactory as PackageCollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;

/**
 * Resolver OrderPickupStatusResolver
 */
class OrderPickupStatusResolver implements OrderPickupStatusResolverInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PackageCollectionFactory
     */
    private $packageCollectionFactory;

    /**
     * OrderPickupStatusResolver constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param PackageCollectionFactory $packageCollectionFactory
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        PackageCollectionFactory $packageCollectionFactory
    ) {
        $this->orderRepository = $orderRepository;
        $this->packageCollectionFactory = $packageCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function getPickupStatusForOrderByOrderId(int $orderId)
    {
        try {
            $order = $this->orderRepository->get($orderId);
            return $this->getPickupStatusForOrder($order);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * @inheritDoc
     */
    public function getPickupStatusForOrder(OrderInterface $order)
    {
        if (!$order->getEntityId() || $order->getShippingMethod() != Method::CLICK_AND_COLLECT_SHIPPING_METHOD) {
            return '';
        }
        if ($order->getState() == Order::STATE_COMPLETE) {
            return self::STATUS_DELIVERED;
        }
        $packageCollection = $this->getOrderPickupPackages($order);
        if (!$packageCollection->getSize()) {
            return self::STATUS_PENDING;
        }
        if ($this->checkOrderDelivered($packageCollection)) {
            return self::STATUS_DELIVERED;
        }
        if ($this->checkOrderReadyToPick($packageCollection)) {
            return self::STATUS_READY_TO_PICK;
        }
        return self::STATUS_PENDING;
    }

    /**
     * Get Order Pickup Packages
     *
     * @param OrderInterface $order
     * @return PackageCollection
     */
    public function getOrderPickupPackages(OrderInterface $order)
    {
        /** @var PackageCollection $packageCollection */
        $packageCollection = $this->packageCollectionFactory->create();
        $packageCollection->addFieldToFilter(PackageInterface::ORDER_ID, $order->getEntityId());
        return $packageCollection;
    }

    /**
     * Check Order Ready To Pick
     *
     * @param PackageCollection $packageCollection
     * @return bool
     */
    public function checkOrderReadyToPick(PackageCollection $packageCollection)
    {
        // Only allow 1 pack available per order
        $listPackageStatus = $packageCollection->getColumnValues(PackageInterface::STATUS);
        return in_array(PackageInterface::STATUS_READY_TO_PICK, $listPackageStatus) ? true : false;
    }

    /**
     * Check Order Delivered
     *
     * @param PackageCollection $packageCollection
     * @return bool
     */
    public function checkOrderDelivered(PackageCollection $packageCollection)
    {
        // Only allow 1 pack available per order
        $listPackageStatus = $packageCollection->getColumnValues(PackageInterface::STATUS);
        return in_array(PackageInterface::STATUS_DELIVERED, $listPackageStatus) ? true : false;
    }
}
