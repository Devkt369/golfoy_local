<?php

declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Ui\Component\Listing;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\ClickAndCollect\Model\ScheduleRepository;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;

/**
 * Model CustomDataProvider
 */
class DataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    const OPEN = 'Open';
    const CLOSE = 'Close';

    /**
     * Get data
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData()
    {
        $scheduleId = $this->request->getParam('schedule_id');

        if ($scheduleId) {
            $objectManager = ObjectManager::getInstance();
            $scheduleRepository = $objectManager->create(
                ScheduleRepository::class
            );
            $schedule = $scheduleRepository->get((int)$scheduleId);

            if ($schedule) {
                return $this->prepareDataForGrid($schedule);
            }
            return [
                'items' => [],
                'totalRecords' => 0
            ];
        } else {
            return [
                'items' => [],
                'totalRecords' => 0
            ];
        }
    }

    /**
     * Function prepareDataForGrid
     *
     * @param ScheduleInterface $schedule
     */
    private function prepareDataForGrid($schedule)
    {
        $scheduleId = $schedule->getScheduleId();
        if ($scheduleId) {
            return [
                'items' => [
                    [
                        'id' => $scheduleId."1",
                        'row_name' => "Status",
                        'monday' => $this->getLabelStatusSchedule($schedule->getMondayStatus()),
                        'tuesday' => $this->getLabelStatusSchedule($schedule->getTuesdayStatus()),
                        'wednesday' => $this->getLabelStatusSchedule($schedule->getWednesdayStatus()),
                        'thursday' => $this->getLabelStatusSchedule($schedule->getThursdayStatus()),
                        'friday' => $this->getLabelStatusSchedule($schedule->getFridayStatus()),
                        'saturday' => $this->getLabelStatusSchedule($schedule->getSaturdayStatus()),
                        'sunday' => $this->getLabelStatusSchedule($schedule->getSundayStatus()),
                    ],
                    [
                        'id' => $scheduleId."2",
                        'row_name' => "Open Time",
                        'monday' => $schedule->getMondayOpen(),
                        'tuesday' => $schedule->getTuesdayOpen(),
                        'wednesday' => $schedule->getWednesdayOpen(),
                        'thursday' => $schedule->getThursdayOpen(),
                        'friday' => $schedule->getFridayOpen(),
                        'saturday' => $schedule->getSaturdayOpen(),
                        'sunday' => $schedule->getSundayOpen(),
                    ],
                    [
                        'id' => $scheduleId."3",
                        'row_name' => "Close Time",
                        'monday' => $schedule->getMondayClose(),
                        'tuesday' => $schedule->getTuesdayClose(),
                        'wednesday' => $schedule->getWednesdayClose(),
                        'thursday' => $schedule->getThursdayClose(),
                        'friday' => $schedule->getFridayClose(),
                        'saturday' => $schedule->getSaturdayClose(),
                        'sunday' => $schedule->getSundayClose(),
                    ]
                ],
                'totalRecords' => 3
            ];
        } else {
            return [
                'items' => [],
                'totalRecords' => 0
            ];
        }
    }

    /**
     * Function getLabelStatusSchedule
     *
     * @param int|null $status
     * @return string
     */
    private function getLabelStatusSchedule($status)
    {
        if ($status) {
            return self::OPEN;
        } else {
            return self::CLOSE;
        }
    }
}
