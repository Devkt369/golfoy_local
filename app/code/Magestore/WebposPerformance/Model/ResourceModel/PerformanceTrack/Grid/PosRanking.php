<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magestore\WebposPerformance\Model\Source\Adminhtml\Ranking\TimeRange;
use Zend_Db_Expr;

/**
 * Class PosRanking
 *
 * Used for pos ranking
 */
class PosRanking extends AbstractCollection
{
    /**
     * @var array
     */
    protected $groupBy = [];

    /**
     * Filter period
     *
     * @return $this
     * @throws Exception
     */
    public function filterPeriod()
    {
        $objectManager = ObjectManager::getInstance();
        /* @var RequestInterface $request */
        $request = $objectManager->get(RequestInterface::class);
        $timeRange = $request->getParam('date_range');
        $from = $request->getParam('from');
        $to = $request->getParam('to');
        $this->initCollection($timeRange, $from, $to);
        return $this;
    }

    /**
     * Get custom range string
     *
     * @return string
     */
    public function getCustomRange()
    {
        return TimeRange::CUSTOM_RANGE;
    }

    /**
     * SEt group by
     *
     * @param array $groupBy
     * @return $this
     */
    public function setGroupBy($groupBy)
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    /**
     * Get group by
     *
     * @return array
     */
    public function getGroupBy()
    {
        return $this->groupBy;
    }

    /**
     * Before load
     *
     * @return $this
     */
    protected function _beforeLoad()
    {
        $totalCountExpr = new Zend_Db_Expr("SUM(total_count)");
        $averageExpr = new Zend_Db_Expr(
            "ROUND(SUM(total_count * average * weight) / SUM(total_count * weight), 4)"
        );
        $this->getSelect()->columns(
            [
                'total_count_sum' => $totalCountExpr,
                'average_period' => $averageExpr,
            ]
        )->joinLeft(
            ['pos_table' => $this->getTable('webpos_pos')],
            "main_table.pos_id = pos_table.pos_id",
            ['pos_name']
        );
        $objectManager = ObjectManager::getInstance();
        /* @var RequestInterface $request */
        $request = $objectManager->get(RequestInterface::class);
        if (in_array('action_code', $this->getGroupBy())) {
            $this->getSelect()->group(
                [
                    'action_code'
                ]
            )->where('main_table.pos_id = "' . $request->getParam('pos_id') . '"')
                ->order('average_period DESC');
        } elseif (in_array('period', $this->getGroupBy())) {
            $this->getSelect()->group(
                [
                    'period'
                ]
            )->where('main_table.pos_id = "' . $request->getParam('pos_id') . '"');
        } else {
            $this->getSelect()->group(
                [
                    'main_table.pos_id'
                ]
            )->order('average_period DESC');
        }
        return parent::_beforeLoad();
    }
}
