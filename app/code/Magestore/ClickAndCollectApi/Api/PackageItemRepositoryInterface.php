<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Interface PackageItemRepositoryInterface
 *
 * Used for Click and collect - PackageItem
 */
interface PackageItemRepositoryInterface
{
    /**
     * Save package
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface $packageItem
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface $packageItem
    ): \Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface;

    /**
     * Find Packages Item by given SearchCriteria
     *
     * SearchCriteria is not required because load all packages is useful case
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageItemSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ): \Magestore\ClickAndCollectApi\Api\Data\PackageItemSearchResultsInterface;

    /**
     * Get Items By Package Id
     *
     * @param int $packageId
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface[]
     */
    public function getItemsByPackageId(int $packageId);
}
