<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposPointy\Model;

use Magestore\ClickAndCollectApi\Api\ScheduleRepositoryInterface;
use Magestore\Webpos\Model\Location\Location;
use Magestore\WebposPointy\Api\PointyConfigManagementInterface;
use Magestore\WebposPointy\Api\PointySignupLocationPrepareDataInterface;
use Magestore\WebposPointy\Api\PointySignupLocationValidationInterface;
use Magestore\WebposPointy\Api\Request\PointySignupInterface;
use Magestore\WebposPointy\Api\Request\PointyKeyInterface;

/**
 * Pointy signup location: prepare data
 */
class PointySignupLocationPrepareData implements PointySignupLocationPrepareDataInterface
{
    /**
     * @var PointySignupLocationValidationInterface
     */
    private $pointySignupValidation;
    /**
     * @var PointyConfigManagementInterface
     */
    private $pointyConfigManagement;
    /**
     * @var ScheduleRepositoryInterface
     */
    private $scheduleRepository;

    /**
     * PointySignupLocationPrepareData constructor.
     * @param PointySignupLocationValidationInterface $pointySignupValidation
     * @param PointyConfigManagementInterface $pointyConfigManagement
     * @param ScheduleRepositoryInterface $scheduleRepository
     */
    public function __construct(
        PointySignupLocationValidationInterface $pointySignupValidation,
        PointyConfigManagementInterface $pointyConfigManagement,
        ScheduleRepositoryInterface $scheduleRepository
    ) {
        $this->pointySignupValidation = $pointySignupValidation;
        $this->pointyConfigManagement = $pointyConfigManagement;
        $this->scheduleRepository = $scheduleRepository;
    }

    /**
     * @inheritDoc
     */
    public function execute(Location $location): array
    {
        return [
            PointyKeyInterface::CLIENT_AUTH_KEY => $this->pointyConfigManagement->getClientAuthKey(),
            PointySignupInterface::PARTNER_REFERENCE_ID => $location->getId(),
            PointySignupInterface::OWNER => [
                PointySignupInterface::OWNER_NAME => $location->getEmail(),
                PointySignupInterface::OWNER_EMAIL => $location->getEmail(),
                PointySignupInterface::OWNER_PHONE_NUMBER => $location->getTelephone(),
            ],
            PointySignupInterface::SHOP => [
                PointySignupInterface::SHOP_NAME => $location->getName(),
                PointySignupInterface::LOCATION => [
                    PointySignupInterface::LAT => $location->getLatitude(),
                    PointySignupInterface::LON => $location->getLongitude(),
                    PointySignupInterface::ADDRESS => $location->getStreet() . ", " . $location->getCity() . ", "
                        . $location->getRegion() . ", " . $location->getCountry() . ", " . $location->getPostcode(),
                    PointySignupInterface::COUNTRY => $location->getCountryId()
                ],
                PointySignupInterface::SHOP_PHONE_NUMBER => $location->getTelephone(),
                PointySignupInterface::OPENING_HOURS => $this->prepareOpeningHours($location)
            ]
        ];
    }

    /**
     * Prepare Opening Hours
     *
     * @param Location $location
     * @return array
     */
    public function prepareOpeningHours(Location $location)
    {
        $schedule = $this->scheduleRepository->get($location->getScheduleId());
        $openingHours = [];
        if ($schedule->getMondayStatus()) {
            $openingHours[PointySignupInterface::MONDAY] = [
                PointySignupInterface::OPENING_MINUTES => $this->convertTime($schedule->getMondayOpen()),
                PointySignupInterface::CLOSING_MINUTES => $this->convertTime($schedule->getMondayClose())
            ];
        }
        if ($schedule->getTuesdayStatus()) {
            $openingHours[PointySignupInterface::TUESDAY] = [
                PointySignupInterface::OPENING_MINUTES => $this->convertTime($schedule->getTuesdayOpen()),
                PointySignupInterface::CLOSING_MINUTES => $this->convertTime($schedule->getTuesdayClose())
            ];
        }
        if ($schedule->getWednesdayStatus()) {
            $openingHours[PointySignupInterface::WEDNESDAY] = [
                PointySignupInterface::OPENING_MINUTES => $this->convertTime($schedule->getWednesdayOpen()),
                PointySignupInterface::CLOSING_MINUTES => $this->convertTime($schedule->getWednesdayClose())
            ];
        }
        if ($schedule->getThursdayStatus()) {
            $openingHours[PointySignupInterface::THURSDAY] = [
                PointySignupInterface::OPENING_MINUTES => $this->convertTime($schedule->getThursdayOpen()),
                PointySignupInterface::CLOSING_MINUTES => $this->convertTime($schedule->getThursdayClose())
            ];
        }
        if ($schedule->getFridayStatus()) {
            $openingHours[PointySignupInterface::FRIDAY] = [
                PointySignupInterface::OPENING_MINUTES => $this->convertTime($schedule->getFridayOpen()),
                PointySignupInterface::CLOSING_MINUTES => $this->convertTime($schedule->getFridayClose())
            ];
        }
        if ($schedule->getSaturdayStatus()) {
            $openingHours[PointySignupInterface::SATURDAY] = [
                PointySignupInterface::OPENING_MINUTES => $this->convertTime($schedule->getSaturdayOpen()),
                PointySignupInterface::CLOSING_MINUTES => $this->convertTime($schedule->getSaturdayClose())
            ];
        }
        if ($schedule->getSundayStatus()) {
            $openingHours[PointySignupInterface::SUNDAY] = [
                PointySignupInterface::OPENING_MINUTES => $this->convertTime($schedule->getSundayOpen()),
                PointySignupInterface::CLOSING_MINUTES => $this->convertTime($schedule->getSundayClose())
            ];
        }

         return $openingHours;
    }

    /**
     * Convert time
     *
     * @param string $time
     * @return int
     */
    public function convertTime(string $time)
    {
        $breakTime = explode(":", $time);
        return (int)$breakTime[0]*60 + (int)$breakTime[1];
    }
}
