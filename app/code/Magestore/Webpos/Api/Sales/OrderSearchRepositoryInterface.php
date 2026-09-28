<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Api\Sales;

/**
 * Interface OrderSearchRepositoryInterface
 *
 * Order search repository api
 */
interface OrderSearchRepositoryInterface
{
    /**
     * Lists orders that match specified search criteria.
     *
     * @param \Magestore\Webpos\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     * @return \Magestore\Webpos\Api\Data\Sales\SimpleOrderSearchResultInterface Order search result interface.
     */
    public function search(\Magestore\Webpos\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Lists all orders that match specified search criteria.
     *
     * @param \Magestore\Webpos\Api\SearchCriteriaInterface $searchCriteria The search criteria.
     * @return \Magestore\Webpos\Api\Data\Sales\SimpleOrderSearchResultInterface Order search result interface.
     */
    public function searchAllOrder(\Magestore\Webpos\Api\SearchCriteriaInterface $searchCriteria);
}
