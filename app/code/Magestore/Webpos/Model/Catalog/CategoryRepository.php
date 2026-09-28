<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Catalog;

use Magento\Catalog\Block\Adminhtml\Category\Tree;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\App\ObjectManager;
use Magestore\Webpos\Api\Catalog\CategoryRepositoryInterface;
use Magestore\Webpos\Api\Data\Catalog\CategorySearchResultsInterface;
use Magestore\Webpos\Helper\Data;
use Magestore\Webpos\Model\ResourceModel\Catalog\Category\Collection;

/**
 * Class CategoryRepository
 *
 * Used for category repository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class CategoryRepository extends \Magento\Catalog\Model\CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @var Tree
     */
    protected $treeCategory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Get category list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return CategorySearchResultsInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $objectManager = ObjectManager::getInstance();
        $this->treeCategory = $objectManager->get(
            Tree::class
        );
        $this->helper = $objectManager->get(
            Data::class
        );

        $store = $this->helper->getCurrentStoreView();
        $storeId = $store->getId();
        $rootCategory = $store->getRootCategoryId();
        $collection = $objectManager->create(
            Collection::class
        );
        $isShowFirstCats = false;
        foreach ($searchCriteria->getFilterGroups() as $group) {
            $fields = [];
            foreach ($group->getFilters() as $filter) {
                $conditionType = $filter->getConditionType() ? $filter->getConditionType() : 'eq';
                if ($filter->getField() == 'first_category') {
                    $isShowFirstCats = true;
                    continue;
                }
                $fields[] = ['attribute' => $filter->getField(), $conditionType => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields);
            }
        }
        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders === null) {
            $sortOrders = [];
        }
        /** @var SortOrder $sortOrder */
        foreach ($sortOrders as $sortOrder) {
            $field = $sortOrder->getField();
            $direction = ($sortOrder->getDirection() == 'ASC') ? 'ASC' : 'DESC';
            $collection->addAttributeToSort($field, $direction);
        }
        if ($isShowFirstCats) {
            $collection->addFieldToFilter('parent_id', $rootCategory);
        }
        $collection->addAttributeToSelect('name');
        $collection->addAttributeToSelect('image');
        $collection->addAttributeToSelect('path');
        $collection->addAttributeToSelect('parent_id');
        $collection->addAttributeToSelect('is_active');
        $collection->addAttributeToFilter(\Magento\Catalog\Model\Category::KEY_IS_ACTIVE, '1');
        $collection->setStoreId($storeId);
        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());
        $collectionSize = $collection->getSize();
        $collection->load();
        $searchResult = $objectManager->get(
            CategorySearchResultsInterface::class
        );
        $searchResult->setSearchCriteria($searchCriteria);
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collectionSize);
        return $searchResult;
    }
}
