<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml\PosRanking;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magestore\Webpos\Model\Source\Adminhtml\Pos as PosSource;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid\PosRanking;
use Magestore\WebposPerformance\Model\ActionTypes;
use Magestore\WebposPerformance\Model\Source\Adminhtml\Ranking\TimeRange;
use Magestore\WebposPerformanceAdminUi\Block\Adminhtml\PosRanking as PosRankingBlock;

/**
 * Class RankingDetail
 *
 * Detail block
 */
class PosDetail extends PosRankingBlock
{
    protected $_template = 'Magestore_WebposPerformanceAdminUi::pos/pos_detail.phtml';

    /* @var PosSource */
    protected $posSource;

    /* @var ActionTypes */
    protected $actionTypes;

    /* @var Date */
    protected $dateFilter;

    /**
     * Internal constructor, that is called from real constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $objectManager = ObjectManager::getInstance();
        $this->posSource = $objectManager->get(PosSource::class);
        $this->actionTypes = $objectManager->get(
            ActionTypes::class
        );
        $this->dateFilter = $objectManager->get(Date::class);
    }

    /**
     * Get ranking pos
     *
     * @return array
     */
    public function getRankingPos()
    {
        $objectManager = ObjectManager::getInstance();
        return $objectManager->create(
            PosRanking::class
        );
    }

    /**
     * Get slowest pos
     */
    public function getSlowestAction()
    {
        $actionPos = $this->getRankingPos()->setGroupBy(['action_code'])->getFirstItem();
        if ($actionPos->getActionCode()) {
            return $actionPos;
        }
    }

    /**
     * Get slowest date
     *
     * @return mixed
     */
    public function getSlowestDate()
    {
        $actionPos = $this->getRankingPos()->setGroupBy(['period'])->getFirstItem();
        if ($actionPos->getActionCode()) {
            return $actionPos;
        } else {
            return null;
        }
    }

    /**
     * Get through put
     *
     * @return float|string
     */
    public function getThroughPut()
    {
        /* @var PosRanking $actionPos */
        $actionPos = $this->getRankingPos()->setGroupBy(['period']);
        $customStart = $this->getRequest()->getParam('from');
        $customEnd = $this->getRequest()->getParam('to');
        $timeRange = $this->getRequest()->getParam('date_range');
        if ($customStart) {
            $customStart = $this->dateFilter->filter($customStart);
        }
        if ($customEnd) {
            $customEnd = $this->dateFilter->filter($customEnd);
        }
        if ($timeRange == null) {
            $timeRange = TimeRange::TODAY;
        }
        [$from, $to] = $actionPos->getDateRange($timeRange, $customStart, $customEnd, true);
        $totalQuery = 0;
        $totalDate = $to->diff($from)->days + 1;
        foreach ($actionPos as $action) {
            $totalQuery = $totalQuery + $action->getTotalCountSum();
        }
        if ($totalDate) {
            return round($totalQuery / $totalDate, 2);
        } else {
            return '';
        }
    }

    /**
     * Get action label
     *
     * @param string $actionCode
     * @return string
     */
    public function getActionLabel(string $actionCode)
    {
        return $this->actionTypes->getActionLabel($actionCode);
    }
}
