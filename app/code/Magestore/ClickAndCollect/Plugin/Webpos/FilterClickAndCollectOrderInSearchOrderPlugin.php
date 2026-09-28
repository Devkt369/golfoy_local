<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Plugin\Webpos;

use Magestore\ClickAndCollectApi\Api\Data\LocationAdditionalDataInterface;
use Magestore\Webpos\Model\Sales\OrderSearchRepository;
use Magestore\Webpos\Model\ResourceModel\Sales\Order\Collection;
use Magestore\Webpos\Api\SearchCriteriaInterface;

/**
 * Plugin Add Filter Click And Collect Order In Webpos Search Order
 */
class FilterClickAndCollectOrderInSearchOrderPlugin
{
    /**
     * Add Filter Click And Collect Order In Webpos Search Order
     *
     * @param OrderSearchRepository $orderSearchRepository
     * @param Collection $collection
     * @param SearchCriteriaInterface $searchCriteria
     * @return Collection
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetOrderCollection(
        OrderSearchRepository $orderSearchRepository,
        Collection $collection,
        SearchCriteriaInterface $searchCriteria
    ) {
        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === LocationAdditionalDataInterface::LOCATION_PICKUP_ID
                    || $filter->getField() === LocationAdditionalDataInterface::PICKUP_STATUS
                    || $filter->getField() === LocationAdditionalDataInterface::PICKUP_DATE_ONLY
                ) {
                    $collection->addFieldToFilter(
                        'main_table.' . $filter->getField(),
                        [$filter->getConditionType() => $filter->getValue()]
                    );
                }
            }
        }
        return $collection;
    }
}
