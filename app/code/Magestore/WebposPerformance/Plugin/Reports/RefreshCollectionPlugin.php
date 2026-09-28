<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Plugin\Reports;

use Exception;
use Magento\Reports\Model\ResourceModel\Refresh\Collection as OriginalRefreshCollection;
use Magestore\WebposPerformance\Model\Statistics\StatisticInterface;
use Magestore\WebposPerformance\Model\StatisticsManagement;

/**
 * Class RefreshCollectionPlugin
 *
 * Used to create Refresh Collection Plugin
 */
class RefreshCollectionPlugin
{
    /**
     * @var StatisticsManagement
     */
    protected $statisticsManagement;

    /**
     * RefreshCollectionPlugin constructor.
     *
     * @param StatisticsManagement $statisticsManagement
     */
    public function __construct(
        StatisticsManagement $statisticsManagement
    ) {
        $this->statisticsManagement = $statisticsManagement;
    }

    /**
     * After load data
     *
     * @param OriginalRefreshCollection $collection
     * @param OriginalRefreshCollection $collectionAfterLoad
     * @return OriginalRefreshCollection|$this
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoadData(OriginalRefreshCollection $collection, $collectionAfterLoad)
    {
        /**
         * @var StatisticInterface[] $posStatistics
         */
        $posStatistics = $this->statisticsManagement->getStatistics();
        if (!empty($posStatistics)) {
            foreach ($posStatistics as $posStatistic) {
                try {
                    $collectionAfterLoad->addItem($this->statisticsManagement->convertToItem($posStatistic));
                } catch (Exception $e) {
                    return $collectionAfterLoad; //Already added - Do nothing
                }
            }
        }
        return $collectionAfterLoad;
    }
}
