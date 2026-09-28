<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api;

/**
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface LocationStoreMapRepositoryInterface
{
    /**
     * Save Location Store Map
     *
     * @param \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface $locationStoreMap
     * @return \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface|void
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface $locationStoreMap
    ): \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface;

    /**
     * Get Location Store Map data by given store pickup id.
     *
     * If you want to create plugin on get method, also you need to create separate
     * Plugin on getList method, because entity loading way is different for these methods
     *
     * @param int $storePickupId
     * @return \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $storePickupId): \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface;

    /**
     * Find Location Store Map by given SearchCriteria
     *
     * SearchCriteria is not required because load all Location Store Map is useful case
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapSearchResultsInterface
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ): \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapSearchResultsInterface;

    /**
     * Delete the Location Store Map data by storePickupId. If location store map is not found do nothing
     *
     * @param int $storePickupId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $storePickupId): void;
}
