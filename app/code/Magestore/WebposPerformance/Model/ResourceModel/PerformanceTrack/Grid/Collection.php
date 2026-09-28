<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid;

/**
 * Class Collection
 *
 * Used for Webpos Performance Track collection
 */
class Collection extends AbstractCollection
{
    /**
     * Before load
     *
     * @return $this
     */
    protected function _beforeLoad()
    {
        $totalCountExpr = new \Zend_Db_Expr("SUM(total_count)");
        $averageExpr = new \Zend_Db_Expr("ROUND(SUM(total_count * average) / SUM(total_count), 4)");
        $status = new \Zend_Db_Expr("IF(". $averageExpr." > 2, 0, 1)");
        $this->getSelect()->columns(
            [
                'total_count_sum' => $totalCountExpr,
                'average_period' => $averageExpr,
                'performance_rate' => $status
            ]
        );
        $this->getSelect()->group(
            [
                'pos_id',
                'action_code',
                'period'
            ]
        );
        return parent::_beforeLoad();
    }
}
