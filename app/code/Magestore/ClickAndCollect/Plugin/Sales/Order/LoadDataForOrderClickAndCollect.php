<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Sales\Order;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\ClickAndCollectApi\Api\Order\IsEnoughQtyInStockInterface;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Magestore\Webpos\Api\Data\Checkout\OrderExtensionInterface;
use Magestore\Webpos\Api\Data\Checkout\OrderExtensionInterfaceFactory;
use Magestore\Webpos\Api\Data\Checkout\OrderInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Psr\Log\LoggerInterface;
use Magestore\ClickAndCollect\Model\Carrier\Method;

/**
 * Class LoadDataForOrderClickAndCollect
 *
 * Load Pickup Location Code for Quote Address.
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LoadDataForOrderClickAndCollect
{
    /**
     * @var OrderExtensionInterfaceFactory
     */
    protected $orderExtensionFactory;

    /**
     * @var PackageRepositoryInterface
     */
    protected $packageRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var LocationRepositoryInterface
     */
    protected $locationRepository;

    /**
     * @var IsEnoughQtyInStockInterface
     */
    protected $isEnoughQtyInStock;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * LoadDataForOrderClickAndCollect constructor.
     * @param OrderExtensionInterfaceFactory $orderExtensionFactory
     * @param PackageRepositoryInterface $packageRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param LocationRepositoryInterface $locationRepository
     * @param IsEnoughQtyInStockInterface $isEnoughQtyInStock
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param Registry $registry
     */
    public function __construct(
        OrderExtensionInterfaceFactory $orderExtensionFactory,
        PackageRepositoryInterface $packageRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        LocationRepositoryInterface $locationRepository,
        IsEnoughQtyInStockInterface $isEnoughQtyInStock,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        Registry $registry
    ) {
        $this->orderExtensionFactory = $orderExtensionFactory;
        $this->packageRepository = $packageRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->locationRepository = $locationRepository;
        $this->isEnoughQtyInStock = $isEnoughQtyInStock;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
    }

    /**
     * Add click and collect data to order extension attributes
     *
     * @param OrderInterface $order
     * @param OrderExtensionInterface|null $orderExtension
     * @return OrderExtensionInterface|null
     */
    public function afterGetExtensionAttributes(
        OrderInterface $order,
        OrderExtensionInterface $orderExtension = null
    ) {
        if ($order->getShippingMethod() !== Method::CLICK_AND_COLLECT_SHIPPING_METHOD) {
            return $orderExtension;
        }
        if ($orderExtension === null) {
            $orderExtension = $this->orderExtensionFactory->create();
        }

        $orderExtension->setLocationPickupId($order->getLocationPickupId());
        $orderExtension->setPickupDateTime($order->getPickupDateTime());
        $orderExtension->setPickupDateOnly($order->getPickupDateOnly());
        $orderExtension->setPickupStatus($order->getPickupStatus());
        $orderExtension->setCustomerFirstname($order->getCustomerFirstname());
        $orderExtension->setCustomerLastname($order->getCustomerLastname());
        $orderExtension->setReadyPickDate($order->getReadyPickDate());

        // Get packages of order
        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter(PackageInterface::ORDER_ID, $order->getEntityId())
            ->create();
        $orderPackages = $this->packageRepository->getList($searchCriteria);
        $orderExtension->setPackages(
            $orderPackages->getItems()
        );

        /* Get is enough qty in stock */
        if ($this->isApiAllowToGetIsEnoughQtyStatus()
            && $order->getPickupStatus() === OrderPickupStatusResolverInterface::STATUS_PENDING) {
            $orderExtension->setIsEnoughQtyInStock(
                $this->isEnoughQtyInStock($order)
            );
        }

        $order->setExtensionAttributes($orderExtension);
        return $orderExtension;
    }

    /**
     * Is Enough Qty In Stock
     *
     * @param OrderInterface $order
     * @return bool
     */
    public function isEnoughQtyInStock(OrderInterface $order)
    {
        try {
            $locationPickupId = $order->getLocationPickupId();
            $location = $this->locationRepository->getById($locationPickupId);
            if ($location->getLocationId()) {
                $magentoOrder = $this->orderRepository->get($order->getEntityId());
                return $this->isEnoughQtyInStock->execute($magentoOrder, $location);
            }
        } catch (LocalizedException $exception) {
            $this->logger->error($exception->getMessage());
        }
        return false;
    }

    /**
     * Get List Api Allow To Get Is Enough Qty Status
     *
     * @return string[]
     */
    public function getListApiAllowToGetIsEnoughQtyStatus()
    {
        return [
            "click-and-collect/load-order-by-increment-id"
        ];
    }

    /**
     * Is Api Allow To Get Is Enough Qty Status
     *
     * @return bool
     */
    public function isApiAllowToGetIsEnoughQtyStatus()
    {
        $posApiUrl = $this->registry->registry('pos_api_url');
        if (!$posApiUrl) {
            return false;
        }
        foreach ($this->getListApiAllowToGetIsEnoughQtyStatus() as $api) {
            if (strpos($posApiUrl, $api) !== false) {
                return true;
            }
        }
        return false;
    }
}
