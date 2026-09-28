<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPerformance\Model\ResourceModel\Report;

use DateTime;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\Timezone\Validator;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Reports\Model\FlagFactory;
use Magento\Reports\Model\ResourceModel\Report\AbstractReport;
use Magestore\WebposPerformance\Model\ActionTypes;
use Magestore\WebposPerformance\Model\Flag;
use Psr\Log\LoggerInterface;
use Zend_Db_Expr;

/**
 * POS Order entity resource model with aggregation by created at
 *
 * Class Performance
 */
class Performance extends AbstractReport
{
    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ActionTypes
     */
    protected $actionTypes;

    /**
     * Createdat constructor.
     *
     * @param Context $context
     * @param LoggerInterface $logger
     * @param TimezoneInterface $localeDate
     * @param FlagFactory $reportsFlagFactory
     * @param Validator $timezoneValidator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param ScopeConfigInterface $scopeConfig
     * @param ActionTypes $actionTypes
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        LoggerInterface $logger,
        TimezoneInterface $localeDate,
        FlagFactory $reportsFlagFactory,
        Validator $timezoneValidator,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        ScopeConfigInterface $scopeConfig,
        ActionTypes $actionTypes,
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
        $this->scopeConfig = $scopeConfig;
        $this->actionTypes = $actionTypes;
    }

    /**
     * Model initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('webpos_performance_aggregated', 'id');
    }

    /**
     * Aggregate POS Orders data by order created at
     *
     * @param string|int|DateTime|array|null $from
     * @param string|int|DateTime|array|null $to
     * @return $this
     * @throws Exception
     */
    public function aggregate($from = null, $to = null)
    {
        $connection = $this->getConnection();
        $aggregationField = 'from';
        $connection->beginTransaction();
        try {
            if ($from !== null || $to !== null) {
                $subSelect = $this->_getTableDateRangeSelect(
                    $this->getTable('webpos_performance_track'),
                    $aggregationField,
                    $aggregationField,
                    $from,
                    $to
                );
            } else {
                $subSelect = null;
            }
            $this->_clearTableByDateRange($this->getMainTable(), $from, $to, $subSelect);

            $periodExpr = $connection->getDatePartSql(
                $this->getStoreTZOffsetQuery(
                    ['o' => $this->getTable('webpos_performance_track')],
                    'o.' . $aggregationField,
                    $from,
                    $to
                )
            );

            $importantActionCode =  $connection->quoteInto(
                "o.action_code IN (?)",
                $this->actionTypes->getCriticalAction()
            );
            $normalActionCode =  $connection->quoteInto(
                "o.action_code IN (?)",
                $this->actionTypes->getHighPriorityAction()
            );

            // Columns list
            $columns = [
                'period' => $periodExpr,
                'action_code' => 'o.action_code',
                'pos_id' => 'o.pos_id',
                'total_count' => new Zend_Db_Expr('SUM(o.total_count)'),
                'average' => new Zend_Db_Expr('SUM(o.total_count * o.average) / SUM(o.total_count)'),
                'weight' => new Zend_Db_Expr('IF('. $importantActionCode . ', 6, IF('
                    . $normalActionCode. ', 3, 1) )'),
            ];
            $select = $connection->select();
            $select->from(
                ['o' => $this->getTable('webpos_performance_track')],
                $columns
            );
            $select->group([$periodExpr, 'o.pos_id', 'o.action_code']);
            $connection->query($select->insertFromSelect($this->getMainTable(), array_keys($columns)));

            $connection->commit();
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }

        $this->_setFlagData(Flag::REPORT_POS_PERFORMANCE_FLAG_CODE);

        return $this;
    }
}
