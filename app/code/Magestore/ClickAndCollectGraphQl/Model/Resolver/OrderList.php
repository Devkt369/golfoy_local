<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Webapi\Request;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magestore\ClickAndCollectGraphQl\Model\Order\OrderAddress;
use Magestore\ClickAndCollectGraphQl\Model\Order\OrderPayments;
use Magestore\ClickAndCollectGraphQl\Model\Resolver\Orders\Query\OrderFilter;
use Magestore\ClickAndCollectApi\Api\PackageRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;
use Magestore\Webpos\Api\Staff\StaffManagementInterface;

/**
 * Orders data resolver
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderList extends AbstractAuthorizeResolver implements ResolverInterface
{
    const ALLOWED_RESOURCE = 'Magestore_ClickAndCollectGraphQl::get_order_list';

    const MAGESTORE_ROLE_RESOURCE = 'Magestore_ClickAndCollectAdminUi::click_and_collect';

    /**
     * Translator field from graphql to collection field
     *
     * @var string[]
     */
    private $fieldTranslatorArray = [
        'number' => 'increment_id',
        'id' => 'entity_id',
        'order_date' => 'created_at'
    ];

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderAddress
     */
    private $orderAddress;

    /**
     * @var OrderPayments
     */
    private $orderPayments;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var OrderFilter
     */
    private $orderFilter;

    /**
     * @var PackageRepositoryInterface
     */
    private $packageRepository;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * OrderList constructor.
     *
     * @param AuthorizationInterface $authorization
     * @param StaffManagementInterface $staffManagement
     * @param Request $request
     * @param OrderRepositoryInterface $orderRepository
     * @param OrderAddress $orderAddress
     * @param OrderPayments $orderPayments
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param SortOrderBuilder $sortOrderBuilder
     * @param OrderFilter $orderFilter
     * @param PackageRepositoryInterface $packageRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        AuthorizationInterface $authorization,
        StaffManagementInterface $staffManagement,
        Request $request,
        OrderRepositoryInterface $orderRepository,
        OrderAddress $orderAddress,
        OrderPayments $orderPayments,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        SortOrderBuilder $sortOrderBuilder,
        OrderFilter $orderFilter,
        PackageRepositoryInterface $packageRepository
    ) {
        parent::__construct($authorization, $staffManagement, $request);
        $this->orderRepository = $orderRepository;
        $this->orderAddress = $orderAddress;
        $this->orderPayments = $orderPayments;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->sortOrderBuilder = $sortOrderBuilder;
        $this->orderFilter = $orderFilter;
        $this->packageRepository = $packageRepository;
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        parent::resolve($field, $context, $info, $value, $args);

        if ($args['currentPage'] < 1) {
            throw new GraphQlInputException(__('currentPage value must be greater than 0.'));
        }
        if ($args['pageSize'] < 1) {
            throw new GraphQlInputException(__('pageSize value must be greater than 0.'));
        }

        try {
            $requestedFields = $info->getFieldSelection(1);
            /* Process get Package from DB only client requests to get package from graphql */
            if (isset($requestedFields['items']['packages'])) {
                $searchResult = $this->getSearchResultWithPackage($args);
            } else {
                $searchResult = $this->getSearchResult($args);
            }

            $maxPages = (int)ceil($searchResult->getTotalCount() / $searchResult->getPageSize());
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return [
            'total_count' => $searchResult->getTotalCount(),
            'items' => $this->formatOrdersArray($searchResult->getItems()),
            'page_info' => [
                'page_size' => $searchResult->getPageSize(),
                'current_page' => $searchResult->getCurPage(),
                'total_pages' => $maxPages,
            ]
        ];
    }

    /**
     * Format order models for graphql schema
     *
     * @param OrderInterface[] $orderModels
     * @return array
     */
    private function formatOrdersArray(array $orderModels)
    {
        $ordersArray = [];
        foreach ($orderModels as $orderModel) {
            $ordersArray[] = [
                'created_at' => $orderModel->getCreatedAt(),
                'grand_total' => $orderModel->getGrandTotal(),
                'id' => $orderModel->getEntityId(),
                'increment_id' => $orderModel->getIncrementId(),
                'number' => $orderModel->getIncrementId(),
                'order_date' => $orderModel->getCreatedAt(),
                'order_number' => $orderModel->getIncrementId(),
                'status' => $orderModel->getStatusLabel(),
                'shipping_method' => $orderModel->getShippingDescription(),
                'shipping_address' => $this->orderAddress->getOrderShippingAddress($orderModel),
                'billing_address' => $this->orderAddress->getOrderBillingAddress($orderModel),
                'payment_methods' => $this->orderPayments->getOrderPaymentMethod($orderModel),
                'location_pickup_id' => $orderModel->getLocationPickupId(),
                'staff_id' => $orderModel->getStaffId(),
                'model' => $orderModel,
                'packages' => $orderModel->getPackages()
            ];
        }
        return $ordersArray;
    }

    /**
     * Get search result from graphql query arguments
     *
     * @param array $args
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     * @throws InputException
     */
    private function getSearchResult(array $args)
    {
        $filterGroups = $this->orderFilter->createFilterGroups($args);
        $this->searchCriteriaBuilder->setFilterGroups($filterGroups);
        if (isset($args['currentPage'])) {
            $this->searchCriteriaBuilder->setCurrentPage($args['currentPage']);
        }
        if (isset($args['pageSize'])) {
            $this->searchCriteriaBuilder->setPageSize($args['pageSize']);
        }
        if (isset($args['sort'])) {
            $sortOrders = [];
            foreach ($args['sort'] as $fieldName => $fieldValue) {
                if (isset($this->fieldTranslatorArray[$fieldName])) {
                    $fieldName = $this->fieldTranslatorArray[$fieldName];
                }
                /** @var SortOrder $sortOrder */
                $sortOrders[] = $this->sortOrderBuilder
                    ->setField($fieldName)
                    ->setDirection(
                        $fieldValue === SortOrder::SORT_DESC ? SortOrder::SORT_DESC : SortOrder::SORT_ASC
                    )->create();
            }
            $this->searchCriteriaBuilder->setSortOrders($sortOrders);
        }
        return $this->orderRepository->getList($this->searchCriteriaBuilder->create());
    }

    /**
     * Get search result from graphql query arguments
     *
     * @param array $args
     * @return \Magento\Sales\Api\Data\OrderSearchResultInterface
     * @throws InputException
     */
    private function getSearchResultWithPackage(array $args)
    {
        $orders= $this->getSearchResult($args);
        $orderIds = [];
        $packageArray = [];

        /* Get order ids to query packages one times */
        foreach ($orders as $order) {
            $orderIds[] = $order->getId();
        }

        /* Get package list provider by the order ids */
        $packageBuilderFilter = $this->searchCriteriaBuilder
            ->addFilter(PackageInterface::ORDER_ID, $orderIds, 'in')
            ->create();
        $packages = $this->packageRepository->getList($packageBuilderFilter)->getItems();

        foreach ($packages as $package) {
            if (!isset($packageArray[$package->getOrderId()])) {
                $packageArray[$package->getOrderId()] = [$package];
            } else {
                $packageArray[$package->getOrderId()][] = $package;
            }
        }

        /* Assign packages to order */
        foreach ($orders as $order) {
            if (isset($packageArray[$order->getId()])) {
                $order->setPackages($packageArray[$order->getId()]);
            } else {
                $order->setPackages([]);
            }
        }

        return $orders;
    }
}
