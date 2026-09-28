<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\SupplierSuccess\Model\ResourceModel\Supplier\Grid;

use Magento\Customer\Ui\Component\DataProvider\Document;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface as FetchStrategy;
use Magento\Framework\Data\Collection\EntityFactoryInterface as EntityFactory;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Module\Manager;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magestore\SupplierSuccess\Model\ResourceModel\Supplier;
use Psr\Log\LoggerInterface as Logger;
use Zend_Db_Expr;

/**
 * Class Collection
 *
 * Collection for grid
 */
class Collection extends SearchResult
{
    /**
     * @inheritdoc
     */
    protected $document = Document::class;

    /**
     * @var Manager
     */
    protected $moduleManager;

    const MAPPING_FIELDS = [
        'total_sku' => 'COUNT(DISTINCT(supplier_product.supplier_product_id))',
        'status' => 'main_table.status',
    ];

    /**
     * Constructor
     *
     * @param EntityFactory $entityFactory
     * @param Logger $logger
     * @param FetchStrategy $fetchStrategy
     * @param EventManager $eventManager
     * @param Manager $moduleManager
     * @param string $mainTable
     * @param string $resourceModel
     * @throws LocalizedException
     */
    public function __construct(
        EntityFactory $entityFactory,
        Logger $logger,
        FetchStrategy $fetchStrategy,
        EventManager $eventManager,
        Manager $moduleManager,
        $mainTable = 'os_supplier',
        $resourceModel = Supplier::class
    ) {
        $this->moduleManager = $moduleManager;
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $mainTable, $resourceModel);
    }

    /**
     * Init select for collection
     *
     * @return $this|Collection|void
     */
    protected function _initSelect()
    {
        $this->getSelect()->from(['main_table' => $this->getMainTable()])
            ->joinLeft(
                ['supplier_product' => $this->getTable('os_supplier_product')],
                'main_table.supplier_id = supplier_product.supplier_id',
                []
            )
            ->columns(
                [
                    'total_sku' => new Zend_Db_Expr(self::MAPPING_FIELDS['total_sku']),
                ]
            )
            ->group('main_table.supplier_id');
        if ($this->moduleManager->isOutputEnabled('Magestore_PurchaseOrderSuccess')) {
            /* Create table of purchase order with SUM purchase order value and get last purchase on */
            $purchaseOrderSelect = clone $this->getSelect();
            $purchaseOrderSelect->reset();
            $purchaseOrderSelect->from(['purchase_order' => $this->getTable('os_purchase_order')]);
            $purchaseOrderSelect->columns(
                [
                    'purchase_order_value' => new Zend_Db_Expr(
                        'SUM(IFNULL(purchase_order.grand_total_incl_tax'
                        . ' / purchase_order.currency_rate,0))'
                    ),
                    'last_purchase_order_on' => new Zend_Db_Expr(
                        'MAX(purchase_order.purchased_at)'
                    )
                ]
            );
            $purchaseOrderSelect->group(['supplier_id']);
            /* End select to table*/

            /* Join with the table selected above */
            $this->getSelect()
                ->joinLeft(
                    ['purchase_order_select' => $purchaseOrderSelect],
                    'main_table.supplier_id = purchase_order_select.supplier_id',
                    [
                        'purchase_order_value' => 'purchase_order_select.purchase_order_value',
                        'last_purchase_order_on' => 'purchase_order_select.last_purchase_order_on'
                    ]
                );
        }
        return $this;
    }

    /**
     * Add field to filter condition
     *
     * @param array|string $field
     * @param null|array|string $condition
     * @return $this|Collection
     */
    public function addFieldToFilter($field, $condition = null)
    {
        foreach (self::MAPPING_FIELDS as $alias => $column) {
            if ($field == $alias) {
                $field = new Zend_Db_Expr($column);
            }
        }
        return parent::addFieldToFilter($field, $condition);
    }

    /**
     * Get collection size
     *
     * @return int
     */
    public function getSize()
    {
        if ($this->_totalRecords === null) {
            $sql = $this->getSelectCountSql();
            $sql->group('main_table.supplier_id');
            $this->_totalRecords = $this->getConnection()->fetchAll($sql, $this->_bindParams);
        }
        return count($this->_totalRecords);
    }

    /**
     * Add select order
     *
     * @param string $field
     * @param string $direction
     * @return $this
     */
    public function setOrder($field, $direction = self::SORT_ORDER_DESC)
    {
        foreach (self::MAPPING_FIELDS as $alias => $column) {
            if ($field == $alias) {
                $field = new Zend_Db_Expr($column);
            }
        }
        return parent::setOrder($field, $direction);
    }

    /**
     * Retrieve all ids for collection
     *
     * @param int|string $limit
     * @param int|string $offset
     * @return array
     */
    public function getAllIds($limit = null, $offset = null)
    {
        $idsSelect = $this->_getClearSelect();
        $idsSelect->columns('main_table.supplier_id');
        $idsSelect->limit($limit, $offset);
        $idsSelect->resetJoinLeft();

        return $this->getConnection()->fetchCol($idsSelect, $this->_bindParams);
    }

    /**
     * Retrieve clear select
     *
     * @return Select
     */
    public function _getClearSelect()
    {
        return $this->_buildClearSelect();
    }

    /**
     * Build clear select
     *
     * @param Select $select
     * @return Select
     */
    public function _buildClearSelect($select = null)
    {
        if (null === $select) {
            $select = clone $this->getSelect();
        }
        $select->reset(Select::ORDER);
        $select->reset(Select::LIMIT_COUNT);
        $select->reset(Select::LIMIT_OFFSET);
        $select->reset(Select::COLUMNS);

        return $select;
    }
}
