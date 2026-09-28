<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model;

/**
 * Class StatisticsManagement
 *
 * Used to create Statistics Management
 */
class StatisticsManagement
{
    /**
     * @var \Magestore\WebposPerformance\Model\FlagFactory
     */
    protected $reportsFlagFactory;

    /**
     * @var Statistics\StatisticInterface[]
     */
    protected $posPerformanceStatistics;

    /**
     * StatisticsManagement constructor.
     *
     * @param FlagFactory $reportsFlagFactory
     * @param Statistics\StatisticInterface[] $posPerformanceStatistics
     */
    public function __construct(
        FlagFactory $reportsFlagFactory,
        $posPerformanceStatistics = []
    ) {
        $this->reportsFlagFactory = $reportsFlagFactory;
        $this->posPerformanceStatistics = $posPerformanceStatistics;
    }

    /**
     * Get statistics
     *
     * @return Statistics\StatisticInterface[]
     */
    public function getStatistics()
    {
        $posStatistics = [];
        if (!empty($this->posPerformanceStatistics)) {
            foreach ($this->posPerformanceStatistics as $posStatistic) {
                if ($posStatistic instanceof Statistics\StatisticInterface) {
                    $posStatistics[] = $posStatistic;
                }
            }
        }
        return $posStatistics;
    }

    /**
     * Convert to item
     *
     * @param Statistics\StatisticInterface $posStatistic
     * @return \Magento\Framework\DataObject
     */
    public function convertToItem(Statistics\StatisticInterface $posStatistic)
    {
        $item = new \Magento\Framework\DataObject();
        $item->setData(
            [
                'id' => $posStatistic->getStatisticId(),
                'report' => $posStatistic->getStatisticTitle(),
                'comment' => $posStatistic->getStatisticComment(),
                'updated_at' => $this->reportsFlagFactory->create()->getPosPerformanceStatisticUpdatedAt($posStatistic)
            ]
        );
        return $item;
    }
}
