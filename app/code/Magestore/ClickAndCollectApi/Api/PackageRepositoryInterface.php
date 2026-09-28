<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Interface PackageRepositoryInterface
 *
 * Used for Click and collect - Package
 */
interface PackageRepositoryInterface
{
    /**
     * Save package
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package
    ): \Magestore\ClickAndCollectApi\Api\Data\PackageInterface;

    /**
     * Get Package data by given package id.
     *
     * If you want to create plugin on get method, also you need to create separate
     * plugin on getList method, because entity loading way is different for these methods
     *
     * @param int $packageId
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $packageId): \Magestore\ClickAndCollectApi\Api\Data\PackageInterface;

    /**
     * Find Packages by given SearchCriteria
     *
     * SearchCriteria is not required because load all packages is useful case
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageSearchResultsInterface
     */
    public function getList(
        ?\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ): \Magestore\ClickAndCollectApi\Api\Data\PackageSearchResultsInterface;

    /**
     * Delete the Package data by package id. If package is not found do nothing
     *
     * @param int $packageId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $packageId): void;
}
