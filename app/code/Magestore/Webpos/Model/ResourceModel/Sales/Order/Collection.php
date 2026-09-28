<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\ResourceModel\Sales\Order;

use Magento\Sales\Api\Data\OrderSearchResultInterface;
use Magento\Sales\Model\ResourceModel\Collection\AbstractCollection;
use Magento\InventorySales\Model\ResourceModel\GetAssignedSalesChannelsDataForStock;
use Magento\Framework\DB\Select;

/**
 * Class Collection
 *
 * Sales order Collection
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Collection extends AbstractCollection implements OrderSearchResultInterface
{
    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magestore\Webpos\Model\Checkout\Order::class,
            \Magento\Sales\Model\ResourceModel\Order::class
        );
    }

    /**
     * Join to sales_item and sales_address table
     *
     * @param string $queryString
     */
    public function joinToGetSearchString($queryString)
    {
        $searchString = new \Zend_Db_Expr(
            "CONCAT(
                GROUP_CONCAT(DISTINCT main_table.increment_id SEPARATOR ', '),
                GROUP_CONCAT(DISTINCT coalesce(order_address.telephone,'') SEPARATOR ', '),
                GROUP_CONCAT(DISTINCT main_table.customer_email SEPARATOR ', '),
                GROUP_CONCAT(DISTINCT coalesce(order_address.middlename,'') SEPARATOR ', '),
                GROUP_CONCAT(IFNULL(main_table.payment_reference_number, '')  SEPARATOR ', '),
                GROUP_CONCAT(
                    IFNULL( CONCAT(order_address.firstname, ' ', order_address.lastname) , '')  
                    SEPARATOR ', '
                ),
                GROUP_CONCAT(
                    IFNULL( CONCAT(order_address.lastname, ' ', order_address.firstname) , '')  
                    SEPARATOR ', '
                )
            )"
        );
        $this->getSelect()
            ->join(
                ['order_address' => $this->getTable('sales_order_address')],
                'main_table.entity_id = order_address.parent_id',
                []
            );
        $this->getSelect()->group('main_table.entity_id');
        $this->getSelect()->having($searchString . ' like "' . $queryString . '"');
    }

    /**
     * Get All Store Ids  which is same Current Stock linked current location
     *
     * @return array
     */
    public function getAllStoreIdsSameCurrentStock()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface $stockManagement */
        $stockManagement = $objectManager->get(
            \Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface::class
        );
        $stockId = $stockManagement->getStockId();
        /** @var GetAssignedSalesChannelsDataForStock $getAssignedSalesChannelsDataForStock */
        $getAssignedSalesChannelsDataForStock = $objectManager->get(
            GetAssignedSalesChannelsDataForStock::class
        );
        $salesChannelsData = $getAssignedSalesChannelsDataForStock->execute($stockId);

        $websiteCodes = [];
        foreach ($salesChannelsData as $salesChannelData) {
            if ($salesChannelData[\Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE]
                !== \Magento\InventorySalesApi\Api\Data\SalesChannelInterface::TYPE_WEBSITE) {
                continue;
            }

            $websiteCodes[] = $salesChannelData[\Magento\InventorySalesApi\Api\Data\SalesChannelInterface::CODE];
        }

        if (empty($websiteCodes)) {
            return [];
        }

        /** @var \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository */
        $websiteRepository = $objectManager->get(\Magento\Store\Api\WebsiteRepositoryInterface::class);
        /** @var \Magento\Store\Api\StoreRepositoryInterface $storeRepository */
        $storeRepository = $objectManager->get(\Magento\Store\Api\StoreRepositoryInterface::class);

        $websiteIds = [];
        foreach ($websiteRepository->getList() as $website) {
            if (!in_array($website->getCode(), $websiteCodes)) {
                continue;
            }
            $websiteIds[] = $website->getId();
        }

        if (empty($websiteCodes)) {
            return [];
        }

        $storeIds = [];
        foreach ($storeRepository->getList() as $store) {
            if (!in_array($store->getWebsiteId(), $websiteIds)) {
                continue;
            }
            $storeIds[] = $store->getId();
        }

        return $storeIds;
    }

    /**
     * Return order out of current stock integrate MSI
     */
    public function ignoreByCurrentStockAndSource()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magestore\Webpos\Api\WebposManagementInterface $webposManagement */
        $webposManagement = $objectManager->get(\Magestore\Webpos\Api\WebposManagementInterface::class);
        /** @var \Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface $stockManagement */
        $stockManagement = $objectManager->get(
            \Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface::class
        );

        if ($stockId = $stockManagement->getStockId()) {
            if ($webposManagement->isWebposStandard()) {
                $storeIds = $this->getAllStoreIdsSameCurrentStock();
                $locationIds = $objectManager->get(\Magestore\Webpos\Api\Location\LocationRepositoryInterface::class)
                    ->getLocationIdsByStockId($stockId);
                $this->getSelect()->where('main_table.store_id NOT IN (?) ', $storeIds)
                    ->where('main_table.pos_location_id NOT IN (?)', $locationIds);
            }
        }
    }

    /**
     * Override cause need left join
     *
     * @return \Magento\Framework\DB\Select
     * @throws \Zend_Db_Select_Exception
     */
    public function getSelectCountSql()
    {
        $this->_renderFilters();

        /* @var Select $select */
        $select = clone $this->getSelect();
        $havingClauses = $select->getPart(Select::HAVING);
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);

        if (empty($havingClauses)) {
            $select->reset(Select::COLUMNS);
            $select->reset(Select::HAVING);
            $select->resetJoinLeft();
            $select->columns(new \Zend_Db_Expr('1'));
        }
        /* @var Select $countSelect */
        $countSelect = clone $select;
        $countSelect->reset();
        $countSelect->from($select, "COUNT(*)");

        return $countSelect;
    }
}
