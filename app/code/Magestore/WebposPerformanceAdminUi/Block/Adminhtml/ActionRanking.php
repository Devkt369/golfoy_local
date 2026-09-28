<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml;

use Magento\Framework\App\ObjectManager;
use Magestore\WebposPerformanceAdminUi\Helper\Config as ConfigHelper;

/**
 * Class ActionRanking
 *
 * Used for Action ranking
 */
class ActionRanking extends AbstractBlock
{
    /**
     * Get reload list url
     *
     * @return string
     */
    public function getReloadListUrl()
    {
        return $this->getUrl('webposperformance/byAction/reloadRankingList');
    }

    /**
     * Get reload detail url
     *
     * @return string
     */
    public function getReloadDetailUrl()
    {
        return $this->getUrl('webposperformance/byAction/reloadDetailAction');
    }

    /**
     * Get number of pos in chart
     *
     * @return string
     */
    public function getNumberOfPosInChart()
    {
        $objectManager = ObjectManager::getInstance();
        $configHelper = $objectManager->get(
            ConfigHelper::class
        );
        return $configHelper->getNumberOfPosInChart();
    }
}
