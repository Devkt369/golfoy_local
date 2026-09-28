<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Customercredit\Api;

/**
 * Interface TransactionRepositoryInterface
 *
 * Used for Customercredit - Transaction
 */
interface TransactionRepositoryInterface
{
    /**
     * Find Transactions by given SearchCriteria
     *
     * SearchCriteria is not required because load all packages is useful case
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Magestore\Customercredit\Api\Data\TransactionSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ): \Magestore\Customercredit\Api\Data\TransactionSearchResultsInterface;
}
