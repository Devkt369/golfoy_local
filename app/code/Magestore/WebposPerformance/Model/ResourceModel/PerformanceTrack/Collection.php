<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack;

use DateTime;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magestore\WebposPerformance\Model\PerformanceTrack as PerformanceTrackModel;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack as InventoryMovementResourceModel;

/**
 * Class Collection
 *
 * Used for Webpos Performance Track collection
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            PerformanceTrackModel::class,
            InventoryMovementResourceModel::class
        );
    }

    /**
     * Filter Track Log
     *
     * @param string $date
     * @param string $posId
     * @return mixed
     * @throws LocalizedException
     */
    public function filterTrackLog(string $date, string $posId)
    {
        $objectManager = ObjectManager::getInstance();
        /* @var TimezoneInterface $timeZone*/
        $timeZone = $objectManager->get(TimezoneInterface::class);
        $fromDate = $date. ' 00:00:00';
        $toDate = $date. ' 23:59:59';
        $collection = $this->addFieldToFilter('pos_id', $posId)
            ->addFieldToFilter('from', ['gteq' => $timeZone->convertConfigTimeToUtc($fromDate)])
            ->addFieldToFilter('from', ['lteq' => $timeZone->convertConfigTimeToUtc($toDate)]);
        $data = $collection->toArray();
        $items = $data['items'];
        foreach ($items as &$logRecord) {
            $logRecord['from'] = $timeZone->date(new DateTime($logRecord['from']))->format('Y-m-d H:i:s');
            $logRecord['to'] = $timeZone->date(new DateTime($logRecord['to']))->format('Y-m-d H:i:s');
        }
        return $items;
    }
}
