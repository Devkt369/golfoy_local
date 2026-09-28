<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\ResourceModel;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Inventory\Model\ResourceModel\StockSourceLink;
use Magestore\Storepickup\Setup\InstallSchema as StorepickupInstallSchema;

/**
 * Pos Location And Store Pickup Map Resource Model
 */
class LocationStoreMap extends AbstractDb
{
    /**#@+
     * Constants related to specific db layer
     */
    const TABLE_NAME = 'webpos_location_storepickup_store_map';
    const ID_FIELD_NAME = 'storepickup_id';
    /**#@-*/

    /**
     * @var string
     */
    protected $_eventPrefix = 'webpos_location_storepickup_store_map_resource_model';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::ID_FIELD_NAME);
    }

    /**
     * Create Location And Store Map Data
     *
     * @throws LocalizedException
     */
    public function createLocationAndStoreMapData()
    {
        $connection = $this->getConnection();
        $connection->truncateTable($this->getMainTable());
        $columns = [
            'storepickup_id' => 's.storepickup_id',
            'source_code' => 's.source_code',
            'stock_id' => new \Zend_Db_Expr('ANY_VALUE(ss.stock_id)'),
            'location_id' => new \Zend_Db_Expr('ANY_VALUE(l.location_id)')
        ];
        $select = $connection->select();
        $select->from(
            ['s' => $this->getTable(StorepickupInstallSchema::SCHEMA_STORE)],
            $columns
        )->joinLeft(
            ['ss' => $this->getStockSourceLinkSelect()],
            's.source_code = ss.source_code',
            []
        )->joinLeft(
            ['l' => $this->getTable('webpos_location')],
            'ss.stock_id = l.stock_id',
            []
        );
        $select->where('ss.count_source = 1');
        $select->group('storepickup_id');
        $connection->query($select->insertFromSelect($this->getMainTable(), array_keys($columns)));
    }

    /**
     * Get Stock Source Link Select
     *
     * @return Select
     */
    public function getStockSourceLinkSelect()
    {
        $select = $this->getConnection()->select();
        $columns = [
            'stock_id' => 'stock_id',
            'source_code' => new \Zend_Db_Expr('ANY_VALUE(source_code)'),
            'count_source' => new \Zend_Db_Expr('COUNT(source_code)')
        ];
        $select->from(
            $this->getTable(StockSourceLink::TABLE_NAME_STOCK_SOURCE_LINK),
            $columns
        )->group('stock_id');
        return $select;
    }

    /**
     * Get Total Items
     *
     * @return int
     * @throws LocalizedException
     */
    public function getTotalItems()
    {
        $countSelect = $this->getConnection()->select();
        $countSelect->from(
            $this->getMainTable(),
            ['count' => new \Zend_Db_Expr('COUNT(*)')]
        );
        $totalItems = $this->getConnection()->fetchOne($countSelect);
        return (int) $totalItems;
    }

    /**
     * Has Unmapped Stores
     *
     * @return bool
     * @throws LocalizedException
     */
    public function hasUnmappedStores()
    {
        $countStoresSelect = $this->getConnection()->select();
        $countStoresSelect->from(
            $this->getTable(StorepickupInstallSchema::SCHEMA_STORE),
            ['count' => new \Zend_Db_Expr('COUNT(*)')]
        );
        $totalStores = (int) $this->getConnection()->fetchOne($countStoresSelect);
        $totalItems = $this->getTotalItems();
        return $totalStores > $totalItems;
    }
}
