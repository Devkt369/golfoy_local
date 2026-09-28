<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Find LocationSpecialDayLink list by SearchCriteria API
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface GetLocationSpecialDayLinksInterface
{
    /**
     * Find LocationSpecialDayLink list by given SearchCriteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkSearchResultsInterface
     */
    public function execute(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    ): \Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkSearchResultsInterface;
}
