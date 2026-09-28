<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Order;

use Magestore\ClickAndCollectApi\Api\Order\GetTotalNeedToPackInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magestore\ClickAndCollectApi\Api\Data\LocationAdditionalDataInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

/**
 * Class GetTotalNeedToPack
 *
 * Get total need to pack process
 */
class GetTotalNeedToPack implements GetTotalNeedToPackInterface
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
     * Execute by location, from and to
     *
     * @param int $locationId
     * @param string $from
     * @param string $to
     * @return int
     */
    public function execute(
        int $locationId,
        string $from,
        string $to
    ): int {
        $pickupStatusAndLocationFilter = $this->searchCriteriaBuilder
            ->addFilter(
                LocationAdditionalDataInterface::PICKUP_STATUS,
                OrderPickupStatusResolver::STATUS_PENDING
            )->addFilter(LocationAdditionalDataInterface::LOCATION_PICKUP_ID, $locationId)
            ->addFilter(
                LocationAdditionalDataInterface::PICKUP_DATE_ONLY,
                $from,
                'gte'
            )->addFilter(
                LocationAdditionalDataInterface::PICKUP_DATE_ONLY,
                $to,
                'lte'
            )
            ->create();

        return $this->orderRepository->getList($pickupStatusAndLocationFilter)->getTotalCount();
    }
}
