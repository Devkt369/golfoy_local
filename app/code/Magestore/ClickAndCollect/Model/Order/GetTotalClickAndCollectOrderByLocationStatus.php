<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magestore\ClickAndCollectApi\Api\Data\LocationAdditionalDataInterface;
use Magestore\ClickAndCollectApi\Api\Order\GetTotalClickAndCollectOrderByLocationStatusInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class GetTotalClickAndCollectOrderByLocationStatus
 *
 * Filter click and collect by location and status
 */
class GetTotalClickAndCollectOrderByLocationStatus implements GetTotalClickAndCollectOrderByLocationStatusInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * GetTotalClickAndCollectOrderByLocationStatus constructor.
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Execute by location and status
     *
     * @param int $locationId
     * @param array $statuses
     * @return int
     */
    public function execute(
        int $locationId,
        array $statuses
    ): int {
        $pickupStatusAndLocationFilter = $this->searchCriteriaBuilder
            ->addFilter(LocationAdditionalDataInterface::PICKUP_STATUS, $statuses, 'in')
            ->addFilter(LocationAdditionalDataInterface::LOCATION_PICKUP_ID, $locationId)
            ->create();

        return $this->orderRepository->getList($pickupStatusAndLocationFilter)->getTotalCount();
    }
}
