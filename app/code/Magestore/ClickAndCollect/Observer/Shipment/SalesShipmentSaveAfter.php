<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\ClickAndCollect\Observer\Shipment;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Shipment;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magestore\ClickAndCollectApi\Api\Package\CancelPackageInterface;

/**
 * Sales Shipment Save After - Cancel ready_to_pick package
 */
class SalesShipmentSaveAfter implements ObserverInterface
{
    /**
     * @var ManagerInterface
     */
    protected $eventManager;

    /**
     * @var PackageRepositoryInterface
     */
    protected $packageRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CancelPackageInterface
     */
    protected $cancelPackage;

    /**
     * SalesShipmentSaveAfter constructor.
     *
     * @param ManagerInterface $eventManager
     * @param PackageRepositoryInterface $packageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CancelPackageInterface $cancelPackage
     */
    public function __construct(
        ManagerInterface $eventManager,
        PackageRepositoryInterface $packageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CancelPackageInterface $cancelPackage
    ) {
        $this->eventManager = $eventManager;
        $this->packageRepository = $packageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->cancelPackage = $cancelPackage;
    }

    /**
     * Cancel ready_to_pick package
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(EventObserver $observer)
    {
        /** @var Shipment $shipment */
        $shipment = $observer->getEvent()->getShipment();
        if ($shipment->getOrigData('entity_id')) {
            return;
        }
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(PackageInterface::ORDER_ID, $shipment->getOrderId())
            ->addFilter(PackageInterface::STATUS, PackageInterface::STATUS_READY_TO_PICK)
            ->create();
        $packages = $this->packageRepository->getList($searchCriteria);
        if (!$packages->getTotalCount()) {
            return;
        }

        foreach ($packages->getItems() as $package) {
            $this->cancelPackage->execute($package);
        }
    }
}
