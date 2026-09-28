<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\Source\Adminhtml\Ranking;

use Magestore\WebposPerformance\Model\Source\Adminhtml\TimeRange as TimeRangeClass;

/**
 * Class TimeRange
 *
 * Used to create Time Range
 */
class TimeRange extends TimeRangeClass
{
    const CUSTOM_RANGE = "custom_range";

    /**
     * Get option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        return [
            self::TODAY => __("Today"),
            self::YESTERDAY => __("Yesterday"),
            self::LAST_7_DAYS => __("Last 7 days"),
            self::LAST_30_DAYS => __("Last 30 days"),
            self::THIS_YEAR => __("This year"),
            self::LAST_YEAR => __("Last year"),
            self::CUSTOM_RANGE => __("Custom range")
        ];
    }
}
