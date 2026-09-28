<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model;

use Magento\Framework\Exception\LocalizedException;

/**
 * Used to create Report Flag Model
 *
 * Class Flag
 */
class Flag extends \Magento\Reports\Model\Flag
{

    const REPORT_POS_PERFORMANCE_FLAG_CODE = 'webpos_pos_performance_aggregated';

    /**
     * Get pos statistic flag code
     *
     * @param Statistics\StatisticInterface $posStatistic
     * @return string
     */
    public function getPosPerformanceStatisticFlagCode(Statistics\StatisticInterface $posStatistic)
    {
        return "webpos_" . $posStatistic->getStatisticId() . "_aggregated";
    }

    /**
     * Get pos statistic updated at
     *
     * @param Statistics\StatisticInterface $posPerformanceStatistic
     * @return string
     * @throws LocalizedException
     */
    public function getPosPerformanceStatisticUpdatedAt(Statistics\StatisticInterface $posPerformanceStatistic)
    {
        $reportCode = $this->getPosPerformanceStatisticFlagCode($posPerformanceStatistic);
        $flag = $this->setReportFlagCode($reportCode)->loadSelf();
        return $flag->hasData() ? $flag->getLastUpdate() : '';
    }
}
