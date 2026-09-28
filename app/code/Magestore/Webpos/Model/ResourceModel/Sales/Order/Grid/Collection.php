<?php
declare(strict_types=1);

namespace Magestore\Webpos\Model\ResourceModel\Sales\Order\Grid;

use Magento\Framework\DB\Select;
use Magento\Sales\Model\ResourceModel\Order\Grid\Collection as OrderGridCollection;
use Zend_Db_Expr;
use Zend_Db_Statement_Exception;

/**
 * Class Collection
 *
 * Used to add payment method to grid colelction
 */
class Collection extends OrderGridCollection
{
    /**
     * @inheritdoc
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        /* Join table to get POS Payment method */
        $this->getSelect()->joinLeft(
            ['webpos_order_payment' => $this->getTable('webpos_order_payment')],
            'webpos_order_payment.order_id = main_table.entity_id',
            ['pos_payment_method' => new Zend_Db_Expr("GROUP_CONCAT(webpos_order_payment.method)")]
        )->group('main_table.entity_id');
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        /* Change where to condition having */
        if ($field === 'pos_payment_method') {
            if (is_array($condition)) {
                foreach ($condition as $key => $value) {
                    if ($key === 'eq') {
                        $conditionSql = $this->_getConditionSql(
                            "GROUP_CONCAT(webpos_order_payment.method)",
                            ['finset' => $value]
                        );
                        $this->getSelect()->having($conditionSql);
                    }
                }
            }
            return $this;
        } else {
            return parent::addFieldToFilter($field, $condition);
        }
    }

    /**
     * Get correct size after join table
     *
     * @return int
     * @throws Zend_Db_Statement_Exception
     */
    public function getSize()
    {
        if ($this->_totalRecords === null) {
            $sql = $this->getSelect()
                ->reset(Select::LIMIT_COUNT)
                ->reset(Select::LIMIT_OFFSET)
                ->__toString();
            $records = $this->getConnection()->query($sql);
            $result = $records->fetchAll();
            $this->_totalRecords = count($result);
        }
        return (int) $this->_totalRecords;
    }
}
