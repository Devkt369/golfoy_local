<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\PickupPackage;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Webapi\Request;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Package\GetCurrentLocationIdInterface;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\AbstractAuthorizeResolver;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;

/**
 * Class ProcessOrderPickupPackageAbstract
 *
 * Abstract class for process package
 */
abstract class ProcessOrderPickupPackageAbstract extends AbstractAuthorizeResolver implements ResolverInterface
{
    /**
     * @var PackageRepositoryInterface
     */
    protected $packageRepository;

    /**
     * @var GetCurrentLocationIdInterface
     */
    protected $getCurrentLocationId;

    /**
     * ProcessOrderPickupPackageAbstract constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param PackageRepositoryInterface $packageRepository
     * @param GetCurrentLocationIdInterface $getCurrentLocationId
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        PackageRepositoryInterface $packageRepository,
        GetCurrentLocationIdInterface $getCurrentLocationId
    ) {
        parent::__construct($authorization, $staffManagement, $request);
        $this->packageRepository = $packageRepository;
        $this->getCurrentLocationId = $getCurrentLocationId;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        parent::resolve($field, $context, $info, $value, $args);

        if (empty($args['input']) || !is_array($args['input'])) {
            throw new GraphQlInputException(__('"input" value should be specified'));
        }
        $this->getCurrentLocationId->setCurrentLocationId($args['input']['location_id']);

        try {
            /** @var PackageInterface $package */
            $package = $this->packageRepository->get($args['input']['package_id']);
        } catch (NoSuchEntityException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        // Do not allow process package cancelled or delivered
        if ($package->getStatus() != PackageInterface::STATUS_READY_TO_PICK) {
            throw new GraphQlInputException(__('Cannot process this package.'));
        }
        if ($package->getLocationId() != $args['input']['location_id']) {
            throw new GraphQlInputException(__('This package is not belong to this location.'));
        }

        $packageProcessResult = $this->processPackage($package);

        if ($packageProcessResult['error']) {
            throw new GraphQlInputException(__($packageProcessResult['error_message']));
        }

        return ['package' => $packageProcessResult['package']->getData()];
    }

    /**
     * Process package in the children class
     *
     * @param PackageInterface $package
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    protected function processPackage(PackageInterface $package)
    {
        // Implement in children class
        return [];
    }
}
