<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\PickupPackage;

use Magento\Framework\AuthorizationInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Webapi\Request;
use Magestore\ClickAndCollect\Model\Order\Email\Sender\OrderSender;
use Magestore\ClickAndCollectApi\Api\Package\SavePackageInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\Package\GetCurrentLocationIdInterface;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\AbstractAuthorizeResolver;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Create order pickup package resolver
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CreateOrderPickupPackage extends AbstractAuthorizeResolver implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::create_package';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::create_package';

    /**
     * @var PackageInterfaceFactory
     */
    private $packageFactory;

    /**
     * @var SavePackageInterface
     */
    private $savePackage;

    /**
     * @var GetCurrentLocationIdInterface
     */
    private $getCurrentLocationId;

    /**
     * @var LocationRepositoryInterface
     */
    private $locationRepository;

    /**
     * @var OrderSender
     */
    private $orderSenderEmail;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * CreateOrderPickupPackage constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param PackageInterfaceFactory $packageFactory
     * @param SavePackageInterface $savePackage
     * @param GetCurrentLocationIdInterface $getCurrentLocationId
     * @param LocationRepositoryInterface $locationRepository
     * @param OrderSender $orderSenderEmail
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        PackageInterfaceFactory $packageFactory,
        SavePackageInterface $savePackage,
        GetCurrentLocationIdInterface $getCurrentLocationId,
        LocationRepositoryInterface $locationRepository,
        OrderSender $orderSenderEmail,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($authorization, $staffManagement, $request);
        $this->packageFactory = $packageFactory;
        $this->savePackage = $savePackage;
        $this->getCurrentLocationId = $getCurrentLocationId;
        $this->locationRepository = $locationRepository;
        $this->orderSenderEmail = $orderSenderEmail;
        $this->orderRepository = $orderRepository;
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
            $this->locationRepository->getById($args['input']['location_id']);
        } catch (\Exception $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        /** @var PackageInterface $package */
        $package = $this->packageFactory->create();
        $package->setPackageId(
            isset($args['input'][PackageInterface::PACKAGE_ID])
                ? $args['input'][PackageInterface::PACKAGE_ID]
                : null
        );
        $package->setOrderId($args['input'][PackageInterface::ORDER_ID]);
        $package->setLocationId($args['input'][PackageInterface::LOCATION_ID]);
        $package->setStatus(
            isset($args['input'][PackageInterface::STATUS])
                ? $args['input'][PackageInterface::STATUS]
                : PackageInterface::STATUS_READY_TO_PICK
        );

        $packageSavedResult = $this->savePackage->execute($package, true);

        if ($packageSavedResult['error']) {
            throw new GraphQlInputException(__($packageSavedResult['error_message']));
        } else {
            // send email when create package successfully
            $order = $this->orderRepository->get($package->getOrderId());
            $this->orderSenderEmail->send($order);
        }

        return ['package' => $packageSavedResult['package']->getData()];
    }
}
