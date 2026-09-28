<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\ClickAndCollectAdminUi\Ui\DataProvider\Location\Form\Modifier;

use Magento\Framework\Registry;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magestore\ClickAndCollectApi\Api\GetLocationSpecialDaysInterface;

/**
 * Click and collect - POS location form modifier
 */
class ClickAndCollect implements ModifierInterface
{
    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var GetLocationSpecialDaysInterface
     */
    protected $getLocationSpecialDays;

    /**
     * ClickAndCollect constructor.
     * @param Registry $registry
     * @param GetLocationSpecialDaysInterface $getLocationSpecialDays
     */
    public function __construct(
        Registry $registry,
        GetLocationSpecialDaysInterface $getLocationSpecialDays
    ) {
        $this->registry = $registry;
        $this->getLocationSpecialDays = $getLocationSpecialDays;
    }

    /**
     * @inheritDoc
     */
    public function modifyData(array $data)
    {
        $location = $this->registry->registry('current_location');
        if ($location && $location->getLocationId()) {
            $specialDaysData = [];
            $specialDaysLocation = $this->getLocationSpecialDays->execute($location);
            foreach ($specialDaysLocation as $item) {
                $specialDaysData[] = [
                    'id' => $item->getSpecialDayId(),
                    'specialday_name' => $item->getSpecialDayName(),
                    'store_status' => $item->getStoreStatus(),
                    'repeat' => $item->getRepeat(),
                    'date_from' => $item->getDateFrom(),
                    'date_to' => $item->getDateTo(),
                    'time_open' => $item->getTimeOpen(),
                    'time_close' => $item->getTimeClose(),
                ];
            }

            $data[$location->getLocationId()]['click_and_collect'] = [
                'section_store_schedule' => [
                    'is_allow_pickup' => $location->getIsAllowPickup(),
                    'schedule_id' => $location->getScheduleId()
                ],
                'session_special_days' => [
                    'special_days_dynamic_grid' => [
                        'links' => [
                            'special_days_list' => $specialDaysData
                        ]
                    ]
                ]
            ];
        }
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function modifyMeta(array $meta)
    {
        return $meta;
    }
}
