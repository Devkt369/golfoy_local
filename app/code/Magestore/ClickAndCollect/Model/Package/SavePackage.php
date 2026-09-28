<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Package;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Package\SavePackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\ValidatePackageInterface;

/**
 * Class Save Package
 *
 * Save package service to process
 */
class SavePackage implements SavePackageInterface
{
    /**
     * @var PackageRepositoryInterface
     */
    private $packageRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ValidatePackageInterface
     */
    private $validatePackage;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderPickupStatusResolverInterface
     */
    private $orderPickupStatusResolver;

    /**
     * SavePackage constructor.
     *
     * @param PackageRepositoryInterface $packageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ValidatePackageInterface $validatePackage
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPickupStatusResolverInterface $orderPickupStatusResolver
     */
    public function __construct(
        PackageRepositoryInterface $packageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ValidatePackageInterface $validatePackage,
        OrderRepositoryInterface $orderRepository,
        OrderPickupStatusResolverInterface $orderPickupStatusResolver
    ) {
        $this->packageRepository = $packageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->validatePackage = $validatePackage;
        $this->orderRepository = $orderRepository;
        $this->orderPickupStatusResolver = $orderPickupStatusResolver;
    }

    /**
     * @inheritDoc
     */
    public function execute(PackageInterface $package, $needUpdatePickupStatus = false)
    {
        $result = [
            'error' => false,
            'error_message' => '',
            'package' => $package
        ];

        if (!$package->getPackageId()) {
            // Validate package data when requesting create a new one
            $validation = $this->validatePackage->execute($package);
            if ($validation['error']) {
                $result['error'] = true;
                $result['error_message'] = $validation['error_message'];
                return $result;
            }

            // Only allow one package per one order
            // Prevent creating 2 ready_to_pick package per order
            $existPackage = $this->getDuplicatePackage($package);
            if ($existPackage) {
                // Update to existing package
                $package->setPackageId($existPackage->getPackageId());
            }
        }

        try {
            $package = $this->packageRepository->save($package);
            $result['package'] = $package;
        } catch (CouldNotSaveException $e) {
            $result = [
                'error' => true,
                'error_message' => $e->getMessage(),
                'package' => $package
            ];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'error_message' => $e->getMessage(),
                'package' => $package
            ];
        }

        // Only update status when package has been saved successfully
        if ($needUpdatePickupStatus && !$result['error']) {
            $this->updateOrderPickupStatus($package);
        }

        return $result;
    }

    /**
     * Get Duplicate Package
     *
     * @param PackageInterface $package
     * @return PackageInterface|null
     */
    public function getDuplicatePackage(PackageInterface $package)
    {
        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(PackageInterface::ORDER_ID, $package->getOrderId())
            ->addFilter(PackageInterface::STATUS, [PackageInterface::STATUS_READY_TO_PICK], 'in')
            ->create();

        $processingPackages = $this->packageRepository->getList($searchCriteria);
        if ($processingPackages->getItems()) {
            return current($processingPackages->getItems());
        }
        return null;
    }

    /**
     * Update Order Pickup Status
     *
     * @param PackageInterface $package
     * @return void
     */
    public function updateOrderPickupStatus(PackageInterface $package)
    {
        try {
            $order = $this->orderRepository->get($package->getOrderId());
            $pickupStatus = $this->orderPickupStatusResolver->getPickupStatusForOrder($order);
            $order->setPickupStatus($pickupStatus);
            $this->orderRepository->save($order);
        } catch (\Exception $e) {
            return;
        }
    }
}
