<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid;

use DateTime;
use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Stdlib\DateTime\Filter\Date;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use Magento\Store\Model\ScopeInterface;
use Magestore\WebposPerformance\Model\Filter;
use Magestore\WebposPerformance\Model\Source\Adminhtml\TimeRange;

/**
 * Class AbstractCollection
 *
 * Used for Webpos Performance Track collection
 */
abstract class AbstractCollection extends SearchResult
{
    /**
     * @var TimezoneInterface
     */
    protected $localeDate;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Date
     */
    protected $dateFilter;

    /**
     * Initialization here
     *
     * @return void
     */
    protected function _construct()
    {
        $objectManager = ObjectManager::getInstance();
        $this->localeDate = $objectManager->get(TimezoneInterface::class);
        $this->scopeConfig = $objectManager->get(ScopeConfigInterface::class);
        $this->filter = $objectManager->get(Filter::class);
        $this->dateFilter = $objectManager->get(Date::class);
    }

    /**
     * Init select
     *
     * @return $this|AbstractCollection|void
     * @throws Exception
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->filterPeriod();
        return $this;
    }

    /**
     * Filter period
     *
     * @return $this
     * @throws Exception
     */
    public function filterPeriod()
    {
        $timeRange = $this->filter->getFilter('time_range');
        $from = $this->filter->getFilter('from');
        $to = $this->filter->getFilter('to');
        $this->initCollection($timeRange, $from, $to);
        return $this;
    }

    /**
     * Init collection
     *
     * @param string|null $timeRange
     * @param string|null $from
     * @param string|null $to
     * @return $this
     * @throws Exception
     */
    public function initCollection($timeRange, $from, $to)
    {
        if ($from) {
            $from = $this->dateFilter->filter($from);
        }
        if ($to) {
            $to = $this->dateFilter->filter($to);
        }
        if (!$timeRange && !$from && !$to) {
            $timeRange = TimeRange::TODAY;
        } elseif (!$timeRange && $from && $to) {
            $timeRange = $this->getCustomRange();
        }
        $this->addPeriodFilter($timeRange, $from, $to);
        return $this;
    }

    /**
     * Add period filter
     *
     * @param string|null $timeRange
     * @param string $customStart
     * @param string $customEnd
     * @return $this
     * @throws Exception
     */
    public function addPeriodFilter($timeRange, $customStart, $customEnd)
    {
        [$from, $to] = $this->getDateRange($timeRange, $customStart, $customEnd, true);
        $this->addFieldToFilter(
            'period',
            [
                'from' => $from->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT),
                'to' => $to->format(\Magento\Framework\Stdlib\DateTime::DATE_PHP_FORMAT)
            ]
        );

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
     * Calculate From and To dates (or times) by given period
     *
     * @param string|null $timeRange
     * @param string $customStart
     * @param string $customEnd
     * @param bool $returnObjects
     * @return array
     * @throws Exception
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getDateRange($timeRange, $customStart, $customEnd, $returnObjects = false)
    {
        $dateEnd = $this->localeDate->date();
        $dateStart = $this->localeDate->date();

        $startMonthDay = explode(
            ',',
            (string)$this->scopeConfig->getValue(
                'reports/dashboard/ytd_start',
                ScopeInterface::SCOPE_STORE
            )
        );
        $startMonth = isset($startMonthDay[0]) ? (int)$startMonthDay[0] : 1;
        $startDay = isset($startMonthDay[1]) ? (int)$startMonthDay[1] : 1;

        switch ($timeRange) {
            case TimeRange::YESTERDAY:
                $dateStart->modify('-1 day');
                $dateEnd->modify('-1 day');
                break;
            case TimeRange::LAST_7_DAYS:
                // substract 6 days we need to include
                // only today and not hte last one from range
                $dateStart->modify('-6 days');
                break;
            case TimeRange::LAST_30_DAYS:
                $dateStart->modify('-30 days');
                break;
            case TimeRange::THIS_YEAR:
                $dateStart->setDate((int) $dateStart->format('Y'), $startMonth, $startDay);
                $dateStart->setTime(0, 0, 0);
                break;
            case TimeRange::LAST_YEAR:
                $dateEnd->setDate((int) $dateStart->format('Y'), $startMonth, $startDay);
                $dateEnd->modify('-1 day');
                $dateEnd->setTime(23, 59, 59);

                $dateStart = clone $dateEnd;
                $dateStart->setDate((int) $dateStart->format('Y'), $startMonth, $startDay);
                $dateStart->setTime(0, 0, 0);
                break;
            case $this->getCustomRange():
                $dateStart = new DateTime($customStart);
                $dateEnd = new DateTime($customEnd);
                break;
            case TimeRange::TODAY:
            default:
                break;
        }

        if ($returnObjects) {
            return [$dateStart, $dateEnd];
        } else {
            return ['from' => $dateStart, 'to' => $dateEnd, 'datetime' => true];
        }
    }
}
