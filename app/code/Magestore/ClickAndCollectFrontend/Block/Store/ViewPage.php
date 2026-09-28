<?php

/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Block\Store;

use Magento\Framework\View\Element\Template;
use Magestore\ClickAndCollect\Helper\Data as HelperData;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;
use Magestore\ClickAndCollectFrontend\Helper\Image as ImageHelper;
use Magestore\ClickAndCollectFrontend\Model\DataProvider\Store\View\CurrentStoreProvider;
use Magestore\Webpos\Api\Data\Location\LocationInterface;

/**
 * Store view block
 */
class ViewPage extends Template
{
    const WEEK_DAYS_LEFT_COLUMN = 'week_days_left_column';
    const WEEK_DAYS_RIGHT_COLUMN = 'week_days_right_column';
    const WEEK_DAY_KEY = 'key';
    const WEEK_DAY_LABEL = 'label';
    const DEFAULT_STORE_ICON_PATH = "Magestore_ClickAndCollectFrontend::images/map_with_pin.png";

    /**
     * @var CurrentStoreProvider
     */
    protected $currentStoreProvider;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * ViewPage constructor.
     * @param Template\Context $context
     * @param CurrentStoreProvider $currentStoreProvider
     * @param HelperData $helperData
     * @param ImageHelper $imageHelper
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        CurrentStoreProvider $currentStoreProvider,
        HelperData $helperData,
        ImageHelper $imageHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->currentStoreProvider = $currentStoreProvider;
        $this->helperData = $helperData;
        $this->imageHelper = $imageHelper;
    }

    /**
     * Get Current Store Provider
     *
     * @return CurrentStoreProvider
     */
    public function getCurrentStoreProvider()
    {
        return $this->currentStoreProvider;
    }

    /**
     * Get Helper Data
     *
     * @return HelperData
     */
    public function getHelperData()
    {
        return $this->helperData;
    }

    /**
     * Get Address
     *
     * @param LocationInterface $store
     * @return string
     */
    public function getAddress(LocationInterface $store)
    {
        $addressArray = [];
        if ($store->getStreet()) {
            $addressArray[] = $store->getStreet();
        }
        if ($store->getRegion()) {
            $addressArray[] = $store->getRegion();
        }
        if ($store->getCity()) {
            $addressArray[] = $store->getCity();
        }
        if ($store->getPostcode()) {
            $addressArray[] = $store->getPostcode();
        }
        if ($store->getCountry()) {
            $addressArray[] = $store->getCountry();
        }
        return implode(', ', $addressArray);
    }

    /**
     * Get media url of image.
     *
     * @param string $imagePath
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrlImage($imagePath = '')
    {
        return $this->imageHelper->getMediaUrlImage($imagePath);
    }

    /**
     * Get Store Json
     *
     * @param LocationInterface $store
     * @return mixed
     */
    public function getStoreJson(LocationInterface $store)
    {
        $store->setAddressData($this->getAddress($store));
        return $store->toJson();
    }

    /**
     * Get Weekdays
     *
     * @return array
     */
    public function getWeekdays()
    {
        return [
            self::WEEK_DAYS_LEFT_COLUMN => [
                [
                    self::WEEK_DAY_KEY => 'monday',
                    self::WEEK_DAY_LABEL => __('Monday')
                ],
                [
                    self::WEEK_DAY_KEY => 'tuesday',
                    self::WEEK_DAY_LABEL => __('Tuesday')
                ],
                [
                    self::WEEK_DAY_KEY => 'wednesday',
                    self::WEEK_DAY_LABEL => __('Wednesday')
                ],
                [
                    self::WEEK_DAY_KEY => 'thursday',
                    self::WEEK_DAY_LABEL => __('Thursday')
                ]
            ],
            self::WEEK_DAYS_RIGHT_COLUMN => [
                [
                    self::WEEK_DAY_KEY => 'friday',
                    self::WEEK_DAY_LABEL => __('Friday')
                ],
                [
                    self::WEEK_DAY_KEY => 'saturday',
                    self::WEEK_DAY_LABEL => __('Saturday')
                ],
                [
                    self::WEEK_DAY_KEY => 'sunday',
                    self::WEEK_DAY_LABEL => __('Sunday')
                ]
            ]
        ];
    }

    /**
     * Get schedule time html in a day.
     *
     * @param ScheduleInterface $schedule
     * @param array $day
     * @return string
     */
    public function getScheduleTimeHtml(ScheduleInterface $schedule, array $day)
    {
        $key = $day[self::WEEK_DAY_KEY];
        $status = (int)$schedule->getData($key . '_status');
        $workingHourInfo = __('Closed');
        if ($status) {
            $workingHourInfo = $schedule->getData($key . '_open') . ' - ' . $schedule->getData($key . '_close');
        }
        return sprintf(
            '<div class="row">
                <div class="container-fluid day-container">
                    <div class="col-xs-4">%s</div>
                    <div class="col-xs-8">%s</div>
                </div>
            </div>',
            $day[self::WEEK_DAY_LABEL],
            $workingHourInfo
        );
    }
}
