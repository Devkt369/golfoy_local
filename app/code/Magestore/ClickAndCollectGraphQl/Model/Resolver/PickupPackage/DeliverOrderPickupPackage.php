<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\PickupPackage;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Webapi\Request;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\GetCurrentLocationIdInterface;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;
use Magestore\ClickAndCollectApi\Api\Package\DeliverPackageInterface;

/**
 * Action process order pickup package resolver
 */
class DeliverOrderPickupPackage extends ProcessOrderPickupPackageAbstract implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::delivery_package';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::delivery_package';

    /**
     * @var DeliverPackageInterface
     */
    protected $deliverPackage;

    /**
     * CancelOrderPickupPackage constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param PackageRepositoryInterface $packageRepository
     * @param GetCurrentLocationIdInterface $getCurrentLocationId
     * @param DeliverPackageInterface $deliverPackage
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        PackageRepositoryInterface $packageRepository,
        GetCurrentLocationIdInterface $getCurrentLocationId,
        DeliverPackageInterface $deliverPackage
    ) {
        parent::__construct($authorization, $staffManagement, $request, $packageRepository, $getCurrentLocationId);
        $this->deliverPackage = $deliverPackage;
    }

    /**
     * Process package in the children class
     *
     * @param PackageInterface $package
     * @return array
     */
    protected function processPackage(PackageInterface $package)
    {
        return $this->deliverPackage->execute($package, true);
    }
}
