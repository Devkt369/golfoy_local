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
use Magestore\ClickAndCollectApi\Api\Package\CancelPackageInterface;

/**
 * Action process order pickup package resolver
 */
class CancelOrderPickupPackage extends ProcessOrderPickupPackageAbstract implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::cancel_package';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::cancel_package';

    /**
     * @var CancelPackageInterface
     */
    protected $cancelPackage;

    /**
     * CancelOrderPickupPackage constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param PackageRepositoryInterface $packageRepository
     * @param GetCurrentLocationIdInterface $getCurrentLocationId
     * @param CancelPackageInterface $cancelPackage
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        PackageRepositoryInterface $packageRepository,
        GetCurrentLocationIdInterface $getCurrentLocationId,
        CancelPackageInterface $cancelPackage
    ) {
        parent::__construct($authorization, $staffManagement, $request, $packageRepository, $getCurrentLocationId);
        $this->cancelPackage = $cancelPackage;
    }

    /**
     * Process package in the children class
     *
     * @param PackageInterface $package
     * @return array
     */
    protected function processPackage(PackageInterface $package)
    {
        return $this->cancelPackage->execute($package, true);
    }
}
