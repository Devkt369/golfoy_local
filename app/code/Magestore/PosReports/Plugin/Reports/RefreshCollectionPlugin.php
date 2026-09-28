<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\PosReports\Plugin\Reports;

use Magento\Reports\Model\ResourceModel\Refresh\Collection as OriginalRefreshCollection;

/**
 * Class RefreshCollectionPlugin
 *
 * Used to create Refresh Collection Plugin
 */
class RefreshCollectionPlugin extends \Magento\Reports\Model\ResourceModel\Refresh\Collection
{
    /**
     * @var \Magestore\PosReports\Model\StatisticsManagement
     */
    protected $statisticsManagement;

    /**
     * RefreshCollectionPlugin constructor.
     *
     * @param \Magestore\PosReports\Model\StatisticsManagement $statisticsManagement
     * @param \Magento\Framework\Data\Collection\EntityFactory $entityFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Reports\Model\FlagFactory $reportsFlagFactory
     */
    public function __construct(
        \Magestore\PosReports\Model\StatisticsManagement $statisticsManagement,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Reports\Model\FlagFactory $reportsFlagFactory
    ) {
        parent::__construct($entityFactory, $localeDate, $reportsFlagFactory);
        $this->statisticsManagement = $statisticsManagement;
    }

    /**
     * After load data
     *
     * @param OriginalRefreshCollection $collection
     * @param OriginalRefreshCollection $collectionAfterLoad
     * @return OriginalRefreshCollection|$this
     * @throws \Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterLoadData(OriginalRefreshCollection $collection, $collectionAfterLoad)
    {
        /**
         * @var \Magestore\PosReports\Model\Statistics\StatisticInterface[] $posStatistics
         */
        $posStatistics = $this->statisticsManagement->getStatistics();
        if (!empty($posStatistics)) {
            foreach ($posStatistics as $posStatistic) {
                try {
                    $collectionAfterLoad->addItem($this->statisticsManagement->convertToItem($posStatistic));
                } catch (\Exception $e) {
                    return $this; //Already added - Do nothing
                }
            }
        }
        return $collectionAfterLoad;
    }
}
