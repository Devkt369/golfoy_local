<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api;

/**
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface SpecialDayRepositoryInterface
{
    /**
     * Save Special Day Data
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface $specialDay
     * @return \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface|void
     * @throws \Magento\Framework\Validation\ValidationException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(
        \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface $specialDay
    ): \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;

    /**
     * Get Special Day data by given specialDayId.
     *
     * If you want to create plugin on get method, also you need to create separate
     * Plugin on getList method, because entity loading way is different for these methods
     *
     * @param int $specialDayId
     * @return \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function get(int $specialDayId): \Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;

    /**
     * Find Special Day by given SearchCriteria
     *
     * SearchCriteria is not required because load all Special Day is useful case
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface|null $searchCriteria
     * @return \Magestore\ClickAndCollectApi\Api\Data\SpecialDaySearchResultsInterface
     */
    public function getList(
        ?\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria = null
    ): \Magestore\ClickAndCollectApi\Api\Data\SpecialDaySearchResultsInterface;

    /**
     * Delete the Special Day data by specialDayId. If special day is not found do nothing
     *
     * @param int $specialDayId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById(int $specialDayId): void;
}
