<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Package;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\CancelPackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\SavePackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\ValidatePackageInterface;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;

/**
 * Class Cancel Package
 *
 * Cancel package service to process
 */
class CancelPackage implements CancelPackageInterface
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
     * @var SavePackageInterface
     */
    private $savePackage;

    /**
     * CancelPackage constructor.
     *
     * @param PackageRepositoryInterface $packageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ValidatePackageInterface $validatePackage
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPickupStatusResolverInterface $orderPickupStatusResolver
     * @param SavePackageInterface $savePackage
     */
    public function __construct(
        PackageRepositoryInterface $packageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ValidatePackageInterface $validatePackage,
        OrderRepositoryInterface $orderRepository,
        OrderPickupStatusResolverInterface $orderPickupStatusResolver,
        SavePackageInterface $savePackage
    ) {
        $this->packageRepository = $packageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->validatePackage = $validatePackage;
        $this->orderRepository = $orderRepository;
        $this->orderPickupStatusResolver = $orderPickupStatusResolver;
        $this->savePackage = $savePackage;
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

        try {
            $package->setStatus(PackageInterface::STATUS_CANCELED);
            $saveResult = $this->savePackage->execute($package, $needUpdatePickupStatus);
            $result['package'] = $saveResult['package'];
        } catch (\Exception $e) {
            $result = [
                'error' => true,
                'error_message' => $e->getMessage(),
                'package' => $package
            ];
        }

        return $result;
    }
}
