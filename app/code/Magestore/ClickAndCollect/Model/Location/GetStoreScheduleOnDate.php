<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Location;

use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\GetStoreScheduleOnDateInterface;
use DateTime;

/**
 * Get location's schedule
 */
class GetStoreScheduleOnDate implements GetStoreScheduleOnDateInterface
{
    /**
     * Get Special Day On Date
     *
     * @param DateTime $date
     * @param SpecialDayInterface[] $specialDays
     * @return SpecialDayInterface|null
     */
    public function getSpecialDayOnDate(
        DateTime $date,
        array $specialDays
    ): ?SpecialDayInterface {
        foreach ($specialDays as $specialDay) {
            $dateFrom = new DateTime($specialDay->getDateFrom(), $date->getTimezone());
            $dateTo = new DateTime($specialDay->getDateTo() . "24:00:00", $date->getTimezone());
            if ($specialDay->getRepeat() === SpecialDayInterface::REPEAT_YEARLY) {
                $year = (int)$date->format('Y');
                $dateFrom->setDate(
                    $year,
                    (int)$dateFrom->format('m'),
                    (int)$dateFrom->format('d')
                );
                $dateTo->setDate(
                    $year,
                    (int)$dateTo->format('m'),
                    (int)$dateTo->format('d')
                );
            }
            if ($dateFrom <= $date && $date <= $dateTo) {
                return $specialDay;
            }
        }
        return null;
    }

    /**
     * @inheritDoc
     */
    public function execute(
        DateTime $date,
        ScheduleInterface $schedule = null,
        array $specialDays = []
    ): ?array {
        if (!$schedule) {
            return null;
        }
        $status = null;
        $open = null;
        $close = null;
        $openTime = null;
        $closeTime = null;
        $specialDay = $this->getSpecialDayOnDate($date, $specialDays);
        if ($specialDay) {
            $status = $specialDay->getStoreStatus();
            if ($status) {
                $openTime = $specialDay->getTimeOpen();
                $closeTime = $specialDay->getTimeClose();
            }
        } else {
            $weekDay = $date->format('l');
            $status = $schedule->{'get' . $weekDay . 'Status'}();
            if ($status) {
                $openTime = $schedule->{'get' . $weekDay . 'Open'}();
                $closeTime = $schedule->{'get' . $weekDay . 'Close'}();
            }
        }
        if ($openTime) {
            $tmp = explode(':', (string)$openTime);
            $open = [
                self::HOUR => (int)$tmp[0],
                self::MINUTE => (int)$tmp[1]
            ];

        }
        if ($closeTime) {
            $tmp = explode(':', (string)$closeTime);
            $close = [
                self::HOUR => (int)$tmp[0],
                self::MINUTE => (int)$tmp[1]
            ];
        }
        return [
            self::STATUS => $status,
            self::OPEN => $open,
            self::CLOSE => $close
        ];
    }
}
