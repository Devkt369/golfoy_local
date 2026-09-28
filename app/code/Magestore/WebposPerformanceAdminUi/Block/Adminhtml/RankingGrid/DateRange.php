<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml\RankingGrid;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ObjectManager;
use Magestore\WebposPerformance\Model\Source\Adminhtml\Ranking\TimeRange;

/**
 * Class DateRange
 *
 * Date Range block
 */
class DateRange extends Template
{
    /**
     * Get list period
     *
     * @return array
     */
    public function getListPeriod()
    {
        $objectManager = ObjectManager::getInstance();
        return $objectManager->get(
            TimeRange::class
        )->getOptionArray();
    }
}
