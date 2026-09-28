<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model\ResourceModel;

use Exception;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Stdlib\DateTime\Timezone\Validator;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Reports\Model\FlagFactory;
use Magento\Sales\Model\Order;
use Magestore\InventoryMovement\Model\Flag;
use Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorInterface;
use Magento\Reports\Model\ResourceModel\Report\AbstractReport;
use Magestore\Webpos\Model\Checkout\PosOrder;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;
use Magestore\InventoryMovement\Helper\Data as Helper;

/**
 * Class OrderPotentialError
 *
 * Used for OrderPotentialError resource model
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class OrderPotentialError extends AbstractReport
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * Resource Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_order_potential_error', OrderPotentialErrorInterface::ID);
    }

    /**
     * OrderPotentialError constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param TimezoneInterface $localeDate
     * @param FlagFactory $reportsFlagFactory
     * @param Validator $timezoneValidator
     * @param DateTime $dateTime
     * @param Helper $helper
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        TimezoneInterface $localeDate,
        FlagFactory $reportsFlagFactory,
        Validator $timezoneValidator,
        DateTime $dateTime,
        Helper $helper,
        $connectionName = null
    ) {
        parent::__construct(
            $context,
            $logger,
            $localeDate,
            $reportsFlagFactory,
            $timezoneValidator,
            $dateTime,
            $connectionName
        );
        $this->helper = $helper;
    }

    /**
     * Aggregate Orders Potential Error Data
     *
     * Return true if found new potential error orders
     *
     * @return bool
     * @throws LocalizedException
     */
    public function aggregate()
    {
        $flagData = $this->_getFlagData(Flag::REPORT_ORDERS_POTENTIAL_ERROR_FLAG_CODE);
        $oldLastId = isset($flagData[Flag::ORDERS_POTENTIAL_ERROR_LAST_ID_KEY]) ?
            $flagData[Flag::ORDERS_POTENTIAL_ERROR_LAST_ID_KEY]
            : 0;

        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $this->removeDataBeforeCollect();

            /* Aggregate data from pos unsynced order */
            $this->aggregatePosUnsyncedOrder();

            /* Aggregate data from magento order */
            $this->aggregateSalesOrder();

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $flagData = [
            Helper::CHECK_ORDERS_SINCE_CONFIG_XML_PATH => $this->helper->getConfigCheckOrderSince()
        ];
        $lastId = $this->getLastId();
        if ($lastId) {
            $flagData[Flag::ORDERS_POTENTIAL_ERROR_LAST_ID_KEY] = $lastId;
        }
        $this->_setFlagData(
            Flag::REPORT_ORDERS_POTENTIAL_ERROR_FLAG_CODE,
            $flagData
        );
        return ($lastId && $lastId > $oldLastId);
    }

    /**
     * Get Last Id
     *
     * @return int|null
     * @throws LocalizedException
     */
    public function getLastId()
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $this->getMainTable(),
            [$this->getIdFieldName()]
        )->order($this->getIdFieldName() . ' ' . Select::SQL_DESC);
        $lastId = $connection->fetchOne($select);
        return $lastId ? (int)$lastId : 0;
    }

    /**
     * Remove Data Before Collect
     *
     * @throws LocalizedException
     */
    public function removeDataBeforeCollect()
    {
        if (!$this->helper->getLastExecutionTime()) {
            $this->_truncateTable($this->getMainTable());
            return;
        }
        $this->deletePosUnsyncedOrderDataBeforeCollect();

        $this->deleteMagentoOrderDataBeforeCollect();
    }

    /**
     * Delete pos unsynced order data before collect
     *
     * @throws LocalizedException
     */
    public function deletePosUnsyncedOrderDataBeforeCollect()
    {
        if (!$this->helper->isWebposEnabled()) {
            return;
        }
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $this->getTable('webpos_order'),
            ['id']
        );
        $posUnsyncedOrderIds = $connection->fetchCol($select);

        if (empty($posUnsyncedOrderIds)) {
            $connection->delete(
                $this->getMainTable(),
                [
                    'type = ?' => OrderPotentialErrorInterface::TYPE_POS_UNSYNCED_ORDER
                ]
            );
        } else {
            $connection->delete(
                $this->getMainTable(),
                [
                    'order_id NOT IN (?)' => $posUnsyncedOrderIds,
                    'type = ?' => OrderPotentialErrorInterface::TYPE_POS_UNSYNCED_ORDER
                ]
            );
        }
    }

    /**
     * Delete Magento Order Data Before Collect
     *
     * @throws LocalizedException
     */
    public function deleteMagentoOrderDataBeforeCollect()
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $this->getTable('sales_order'),
            ['entity_id']
        )->where("updated_at > ?", $this->helper->getLastExecutionTime());
        $magentoOrderIds = $connection->fetchCol($select);
        $connection->delete(
            $this->getMainTable(),
            [
                'order_id IN (?)' => $magentoOrderIds,
                'type = ?' => OrderPotentialErrorInterface::TYPE_MAGENTO_ORDER
            ]
        );
    }

    /**
     * Get From Time
     *
     * @return \DateTime|string|null
     */
    public function getFromTime()
    {
        return $this->helper->getLastExecutionTime() ?
            $this->helper->getLastExecutionTime()
            : $this->helper->getDayFrom();
    }

    /**
     * Aggregate Pos Unsynced Order
     *
     * @throws LocalizedException
     */
    public function aggregatePosUnsyncedOrder()
    {
        if (!$this->helper->isWebposEnabled()) {
            return;
        }
        $connection = $this->getConnection();

        // Columns list
        $columns = [
            'order_id' => 'wo.id',
            'order_increment_id' => 'wo.increment_id',
            'created_at' => 'wo.order_created_time',
            'store_id' => 'wo.store_id',
            'type' => new Zend_Db_Expr("'" . OrderPotentialErrorInterface::TYPE_POS_UNSYNCED_ORDER . "'"),
            'information' => new Zend_Db_Expr("'" . __('Order was not synchronized from POS to Magento') . "'")
        ];

        $select = $connection->select();

        $select->from(
            ['wo' => $this->getTable('webpos_order')],
            $columns
        );
        $select->where(
            'wo.status = ' . PosOrder::STATUS_FAILED . ' OR ' . 'wo.status = ' . PosOrder::STATUS_PROCESSING
        );
        $select->where("created_at > ?", $this->getFromTime());

        $connection->query($select->insertFromSelect($this->getMainTable(), array_keys($columns)));
    }

    /**
     * Get Information Select
     *
     * @return string|null
     */
    public function getInformationSelect()
    {
        $conditions = $this->getSelectOrdersPotentialErrorConditions();
        if (empty($conditions)) {
            return null;
        }
        $result = '';
        foreach ($conditions as $condition) {
            if (!$result) {
                $result .= 'INSERT(CONCAT(';
            } else {
                $result .= ' , ';
            }
            $result .= "(CASE WHEN " . $condition['condition']
                . " THEN ', " . $condition['information'] . "' ELSE '' END)";
        }
        $result .= "), 1, 2, '')";
        return new Zend_Db_Expr($result);
    }

    /**
     * Aggregate Sales Order
     *
     * @throws LocalizedException
     */
    public function aggregateSalesOrder()
    {
        $connection = $this->getConnection();
        // Columns list
        $columns = [
            'order_id' => 'o.entity_id',
            'order_increment_id' => 'o.increment_id',
            'created_at' => 'o.created_at',
            'store_id' => 'o.store_id',
            'type' => new Zend_Db_Expr("'" . OrderPotentialErrorInterface::TYPE_MAGENTO_ORDER . "'"),
            'status' => 'o.status',
            'state' => 'o.state',
            'base_currency_code' => 'o.base_currency_code',
            'grand_total' => 'o.base_grand_total',
            'total_paid_amount' => $connection->getIfNullSql('o.base_total_paid', 0),
            'total_refunded_amount' => $connection->getIfNullSql('o.base_total_refunded', 0),
            'total_qty_ordered' => 'o.total_qty_ordered',
            'total_qty_shipped' => 'oi.total_qty_shipped',
            'total_qty_invoiced' => 'oi.total_qty_invoiced',
            'total_qty_refunded' => 'oi.total_qty_refunded',
            'total_qty_canceled' => 'oi.total_qty_canceled',
            'total_virtual_qty' => 'oi.total_virtual_qty',
            'total_qty_shipped_actual' => $connection->getIfNullSql('si.total_qty_shipped_actual', 0),
            'total_qty_invoiced_actual' => $connection->getIfNullSql('ii.total_qty_invoiced_actual', 0),
            'total_qty_refunded_actual' => $connection->getIfNullSql('ci.total_qty_refunded_actual', 0)
        ];

        $informationSelect = $this->getInformationSelect();
        if ($informationSelect) {
            $columns['information'] = $informationSelect;
        }

        $select = $this->getConnection()->select();
        $select->from(
            ['o' => $this->getTable('sales_order')],
            $columns
        )->joinLeft(
            ['oi' => $this->getOrderItemsSelect()],
            'oi.order_id = o.entity_id',
            []
        )->joinLeft(
            ['si' => $this->getShipmentItemSelect()],
            'si.si_order_id = o.entity_id',
            []
        )->joinLeft(
            ['ii' => $this->getInvoiceItemSelect()],
            'ii.ii_order_id = o.entity_id',
            []
        )->joinLeft(
            ['ci' => $this->getCreditmemoItemSelect()],
            'ci.ci_order_id = o.entity_id',
            []
        );
        $select->where("o.updated_at > ?", $this->getFromTime());
        $select = $this->addSelectOrdersPotentialErrorConditions($select);

        $connection->query($select->insertFromSelect($this->getMainTable(), array_keys($columns)));
    }

    /**
     * Get Order Items Select
     *
     * @return Select
     */
    public function getOrderItemsSelect()
    {
        $selectOrderItem = $this->getConnection()->select();
        $orderItemCols = [
            'order_id' => 'order_id',
            'total_qty_shipped' => new Zend_Db_Expr('SUM(qty_shipped)'),
            'total_qty_invoiced' => new Zend_Db_Expr('SUM(qty_invoiced)'),
            'total_qty_refunded' => new Zend_Db_Expr('SUM(qty_refunded)'),
            'total_qty_canceled' => new Zend_Db_Expr('SUM(qty_canceled)'),
            'total_virtual_qty' => new Zend_Db_Expr('SUM(CASE WHEN is_virtual = 1 THEN qty_ordered ELSE 0 END)')
        ];
        $selectOrderItem->from(
            $this->getTable('sales_order_item'),
            $orderItemCols
        )->where(
            'parent_item_id IS NULL'
        )->group(
            'order_id'
        );
        return $selectOrderItem;
    }

    /**
     * Get Shipment Item Select
     *
     * @return Select
     */
    public function getShipmentItemSelect()
    {
        $select = $this->getConnection()->select();
        $columns = [
            'si_order_id' => 'oi.order_id',
            'total_qty_shipped_actual' => new Zend_Db_Expr('SUM(qty)'),
        ];
        $select->from(
            $this->getTable('sales_shipment_item'),
            $columns
        )->join(
            ['oi' => $this->getTable('sales_order_item')],
            'order_item_id = oi.item_id'
        )->where(
            'oi.parent_item_id IS NULL'
        )->group(
            'si_order_id'
        );
        return $select;
    }

    /**
     * Get Invoice Item Select
     *
     * @return Select
     */
    public function getInvoiceItemSelect()
    {
        $select = $this->getConnection()->select();
        $columns = [
            'ii_order_id' => 'oi.order_id',
            'total_qty_invoiced_actual' => new Zend_Db_Expr('SUM(qty)'),
        ];
        $select->from(
            $this->getTable('sales_invoice_item'),
            $columns
        )->join(
            ['oi' => $this->getTable('sales_order_item')],
            'order_item_id = oi.item_id'
        )->where(
            'oi.parent_item_id IS NULL'
        )->group(
            'ii_order_id'
        );
        return $select;
    }

    /**
     * Get Creditmemo Item Select
     *
     * @return Select
     */
    public function getCreditmemoItemSelect()
    {
        $select = $this->getConnection()->select();
        $columns = [
            'ci_order_id' => 'oi.order_id',
            'total_qty_refunded_actual' => new Zend_Db_Expr('SUM(qty)'),
        ];
        $select->from(
            $this->getTable('sales_creditmemo_item'),
            $columns
        )->join(
            ['oi' => $this->getTable('sales_order_item')],
            'order_item_id = oi.item_id'
        )->where(
            'oi.parent_item_id IS NULL'
        )->group(
            'ci_order_id'
        );
        return $select;
    }

    /**
     * Add Select Orders Potential Error Conditions
     *
     * @param Select $select
     * @return Select
     */
    public function addSelectOrdersPotentialErrorConditions(Select $select)
    {
        $conditions = $this->getSelectOrdersPotentialErrorConditions();
        $orWhereConditions = "";
        foreach ($conditions as $condition) {
            if (empty($orWhereConditions)) {
                $orWhereConditions = $condition['condition'];
            } else {
                $orWhereConditions .= ' OR ' . $condition['condition'];
            }
        }

        $select->where($orWhereConditions);
        return $select;
    }

    /**
     * Get Select Orders Potential Error Conditions
     *
     * @return array[]
     */
    public function getSelectOrdersPotentialErrorConditions()
    {
        $connection = $this->getConnection();
        return [
            [
                'condition' =>
                    "("
                    . "(total_qty_shipped <> " . $connection->getIfNullSql('total_qty_shipped_actual', 0) . ")"
                    . " OR (state = '" . Order::STATE_COMPLETE . "' AND ("
                    . new Zend_Db_Expr('total_qty_shipped + total_virtual_qty') . ") = 0)"
                    . ")",
                'information' => __("Shipment Error")
            ],
            [
                'condition' =>
                    "("
                    . "(total_qty_invoiced <> " . $connection->getIfNullSql('total_qty_invoiced_actual', 0) . ")"
                    . " OR (state = '" . Order::STATE_COMPLETE . "' AND total_qty_ordered <> total_qty_invoiced)"
                    . ")",
                'information' => __("Invoice Error")
            ],
            [
                'condition' =>
                    "("
                    . "(total_qty_refunded <> " . $connection->getIfNullSql('total_qty_refunded_actual', 0) . ")"
                    . " OR (state = '" . Order::STATE_CLOSED . "' AND total_qty_ordered <> total_qty_refunded)"
                    . ")",
                'information' => __("Credit Memo Error")
            ]
        ];
    }
}
