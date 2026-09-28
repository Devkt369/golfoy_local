<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Package;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Package\SavePackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\DeliverPackageInterface;
use Magestore\ClickAndCollectApi\Api\Package\ValidatePackageInterface;
use Magento\Sales\Api\ShipOrderInterface;
use Magestore\ClickAndCollectApi\Api\PackageItemRepositoryInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterface;
use Magento\Sales\Api\Data\ShipmentItemCreationInterfaceFactory;
use Magento\Framework\App\RequestInterface;
use Magestore\Webpos\Model\Checkout\PosOrder;

/**
 * Class Deliver Package
 *
 * Deliver package to process
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class DeliverPackage implements DeliverPackageInterface
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
     * @var ShipOrderInterface
     */
    private $shipOrder;

    /**
     * @var PackageItemRepositoryInterface
     */
    private $packageItemRepository;

    /**
     * @var ShipmentItemCreationInterfaceFactory
     */
    private $shipmentItemCreationInterfaceFactory;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * DeliverPackage constructor.
     *
     * @param PackageRepositoryInterface $packageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ValidatePackageInterface $validatePackage
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderPickupStatusResolverInterface $orderPickupStatusResolver
     * @param SavePackageInterface $savePackage
     * @param ShipOrderInterface $shipOrder
     * @param PackageItemRepositoryInterface $packageItemRepository
     * @param ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory
     * @param RequestInterface $request
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        PackageRepositoryInterface $packageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        ValidatePackageInterface $validatePackage,
        OrderRepositoryInterface $orderRepository,
        OrderPickupStatusResolverInterface $orderPickupStatusResolver,
        SavePackageInterface $savePackage,
        ShipOrderInterface $shipOrder,
        PackageItemRepositoryInterface $packageItemRepository,
        ShipmentItemCreationInterfaceFactory $shipmentItemCreationInterfaceFactory,
        RequestInterface $request
    ) {
        $this->packageRepository = $packageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->validatePackage = $validatePackage;
        $this->orderRepository = $orderRepository;
        $this->orderPickupStatusResolver = $orderPickupStatusResolver;
        $this->savePackage = $savePackage;
        $this->shipOrder = $shipOrder;
        $this->packageItemRepository = $packageItemRepository;
        $this->shipmentItemCreationInterfaceFactory = $shipmentItemCreationInterfaceFactory;
        $this->request = $request;
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
            $requestParams = $this->request->getParams();
            $requestParams[PosOrder::PARAM_ORDER_LOCATION_ID] = $package->getLocationId();
            $this->request->setParams($requestParams);

            $this->processShipmentFromPackage($package);

            $package->setStatus(PackageInterface::STATUS_DELIVERED);
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

    /**
     * Process Shipment From Package
     *
     * @param PackageInterface $package
     */
    public function processShipmentFromPackage(PackageInterface $package)
    {
        try {
            $order = $this->orderRepository->get($package->getOrderId());
            $packageItems = $this->packageItemRepository->getItemsByPackageId($package->getPackageId());
            $packageItemsQty = [];
            foreach ($packageItems as $packageItem) {
                $packageItemsQty[$packageItem->getItemId()] = $packageItem->getPackedQty();
                if ($packageItem->getParentItemId()) {
                    $packageItemsQty[$packageItem->getParentItemId()] = $packageItem->getPackedQty();
                }
            }
            $shipmentItems = $this->getQuantitiesFromOrderItems($order->getItems(), $packageItemsQty);
            if (!count($shipmentItems)) {
                throw new LocalizedException(__('Cannot create shipment without items'));
            }
            $this->shipOrder->execute($order->getEntityId(), $shipmentItems);
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * Translate OrderItemInterface array to product id => product quantity array.
     *
     * @param OrderItemInterface[] $items
     * @param array $packageItemsQty
     * @return ShipmentItemCreationInterface[]
     * @see \Magento\Sales\Model\Order\ShipmentDocumentFactory
     */
    public function getQuantitiesFromOrderItems(array $items, array $packageItemsQty)
    {
        $shipmentItems = [];
        foreach ($items as $item) {
            if (!$item->getIsVirtual()
                && ((!$item->getParentItem() && !$item->isShipSeparately())
                    || ($item->getParentItem() && $item->isShipSeparately()))
                && isset($packageItemsQty[$item->getItemId()])) {
                /** @var ShipmentItemCreationInterface $shipmentItem */
                $shipmentItem = $this->shipmentItemCreationInterfaceFactory->create();
                $shipmentItem->setOrderItemId($item->getItemId());
                $shipmentItem->setQty($packageItemsQty[$item->getItemId()]);
                $shipmentItems[] = $shipmentItem;
            }
        }
        return $shipmentItems;
    }
}
