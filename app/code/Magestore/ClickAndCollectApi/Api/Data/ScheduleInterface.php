<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Data;

/**
 * Represents Schedule Interface
 *
 * @api
 */
interface ScheduleInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const SCHEDULE_ID = 'schedule_id';
    const SCHEDULE_NAME = 'schedule_name';

    const MONDAY_STATUS = 'monday_status';
    const TUESDAY_STATUS = 'tuesday_status';
    const WEDNESDAY_STATUS = 'wednesday_status';
    const THURSDAY_STATUS = 'thursday_status';
    const FRIDAY_STATUS = 'friday_status';
    const SATURDAY_STATUS = 'saturday_status';
    const SUNDAY_STATUS = 'sunday_status';

    const MONDAY_OPEN = 'monday_open';
    const TUESDAY_OPEN = 'tuesday_open';
    const WEDNESDAY_OPEN = 'wednesday_open';
    const THURSDAY_OPEN = 'thursday_open';
    const FRIDAY_OPEN = 'friday_open';
    const SATURDAY_OPEN = 'saturday_open';
    const SUNDAY_OPEN = 'sunday_open';

    const MONDAY_CLOSE = 'monday_close';
    const TUESDAY_CLOSE = 'tuesday_close';
    const WEDNESDAY_CLOSE = 'wednesday_close';
    const THURSDAY_CLOSE = 'thursday_close';
    const FRIDAY_CLOSE = 'friday_close';
    const SATURDAY_CLOSE = 'saturday_close';
    const SUNDAY_CLOSE = 'sunday_close';

    /**
     * Constants for status of store
     */
    const OPEN_STATUS = 1;
    const CLOSE_STATUS = 0;

    /**
     * Get schedule id
     *
     * @return int|null
     */
    public function getScheduleId(): ?int;

    /**
     * Set schedule id
     *
     * @param int|null $scheduleId
     * @return void
     */
    public function setScheduleId(?int $scheduleId): void;

    /**
     * Get schedule name
     *
     * @return string|null
     */
    public function getScheduleName(): ?string;

    /**
     * Set schedule name
     *
     * @param string|null $scheduleName
     * @return void
     */
    public function setScheduleName(?string $scheduleName): void;

    /**
     * Get monday status
     *
     * @return int|null
     */
    public function getMondayStatus(): ?int;

    /**
     * Set monday status
     *
     * @param int|null $mondayStatus
     * @return void
     */
    public function setMondayStatus(?int $mondayStatus): void;

    /**
     * Get tuesday status
     *
     * @return int|null
     */
    public function getTuesdayStatus(): ?int;

    /**
     * Set tuesday status
     *
     * @param int|null $tuesdayStatus
     * @return void
     */
    public function setTuesdayStatus(?int $tuesdayStatus): void;

    /**
     * Get wednesday status
     *
     * @return int|null
     */
    public function getWednesdayStatus(): ?int;

    /**
     * Set wednesday status
     *
     * @param int|null $wednesdayStatus
     * @return void
     */
    public function setWednesdayStatus(?int $wednesdayStatus): void;

    /**
     * Get thursday status
     *
     * @return int|null
     */
    public function getThursdayStatus(): ?int;

    /**
     * Set thursday status
     *
     * @param int|null $thursdayStatus
     * @return void
     */
    public function setThursdayStatus(?int $thursdayStatus): void;

    /**
     * Get friday status
     *
     * @return int|null
     */
    public function getFridayStatus(): ?int;

    /**
     * Set friday status
     *
     * @param int|null $fridayStatus
     * @return void
     */
    public function setFridayStatus(?int $fridayStatus): void;

    /**
     * Get saturday status
     *
     * @return int|null
     */
    public function getSaturdayStatus(): ?int;

    /**
     * Set saturday status
     *
     * @param int|null $saturdayStatus
     * @return void
     */
    public function setSaturdayStatus(?int $saturdayStatus): void;

    /**
     * Get sunday status
     *
     * @return int|null
     */
    public function getSundayStatus(): ?int;

    /**
     * Set sunday status
     *
     * @param int|null $sundayStatus
     * @return void
     */
    public function setSundayStatus(?int $sundayStatus): void;

    /**
     * Get monday open
     *
     * @return string|null
     */
    public function getMondayOpen(): ?string;

    /**
     * Set monday open
     *
     * @param string|null $mondayOpen
     * @return void
     */
    public function setMondayOpen(?string $mondayOpen): void;

    /**
     * Get tuesday open
     *
     * @return string|null
     */
    public function getTuesdayOpen(): ?string;

    /**
     * Set tuesday open
     *
     * @param string|null $tuesdayOpen
     * @return void
     */
    public function setTuesdayOpen(?string $tuesdayOpen): void;

    /**
     * Get wednesday open
     *
     * @return string|null
     */
    public function getWednesdayOpen(): ?string;

    /**
     * Set wednesday open
     *
     * @param string|null $wednesdayOpen
     * @return void
     */
    public function setWednesdayOpen(?string $wednesdayOpen): void;

    /**
     * Get thursday open
     *
     * @return string|null
     */
    public function getThursdayOpen(): ?string;

    /**
     * Set thursday open
     *
     * @param string|null $thursdayOpen
     * @return void
     */
    public function setThursdayOpen(?string $thursdayOpen): void;

    /**
     * Get friday open
     *
     * @return string|null
     */
    public function getFridayOpen(): ?string;

    /**
     * Set friday open
     *
     * @param string|null $fridayOpen
     * @return void
     */
    public function setFridayOpen(?string $fridayOpen): void;

    /**
     * Get saturday open
     *
     * @return string|null
     */
    public function getSaturdayOpen(): ?string;

    /**
     * Set saturday open
     *
     * @param string|null $saturdayOpen
     * @return void
     */
    public function setSaturdayOpen(?string $saturdayOpen): void;

    /**
     * Get sunday open
     *
     * @return string|null
     */
    public function getSundayOpen(): ?string;

    /**
     * Set sunday open
     *
     * @param string|null $sundayOpen
     * @return void
     */
    public function setSundayOpen(?string $sundayOpen): void;

    /**
     * Get monday close
     *
     * @return string|null
     */
    public function getMondayClose(): ?string;

    /**
     * Set monday close
     *
     * @param string|null $mondayClose
     * @return void
     */
    public function setMondayClose(?string $mondayClose): void;

    /**
     * Get tuesday close
     *
     * @return string|null
     */
    public function getTuesdayClose(): ?string;

    /**
     * Set tuesday close
     *
     * @param string|null $tuesdayClose
     * @return void
     */
    public function setTuesdayClose(?string $tuesdayClose): void;

    /**
     * Get wednesday close
     *
     * @return string|null
     */
    public function getWednesdayClose(): ?string;

    /**
     * Set wednesday close
     *
     * @param string|null $wednesdayClose
     * @return void
     */
    public function setWednesdayClose(?string $wednesdayClose): void;

    /**
     * Get thursday close
     *
     * @return string|null
     */
    public function getThursdayClose(): ?string;

    /**
     * Set thursday close
     *
     * @param string|null $thursdayClose
     * @return void
     */
    public function setThursdayClose(?string $thursdayClose): void;

    /**
     * Get friday close
     *
     * @return string|null
     */
    public function getFridayClose(): ?string;

    /**
     * Set friday close
     *
     * @param string|null $fridayClose
     * @return void
     */
    public function setFridayClose(?string $fridayClose): void;

    /**
     * Get saturday close
     *
     * @return string|null
     */
    public function getSaturdayClose(): ?string;

    /**
     * Set saturday close
     *
     * @param string|null $saturdayClose
     * @return void
     */
    public function setSaturdayClose(?string $saturdayClose): void;

    /**
     * Get sunday close
     *
     * @return string|null
     */
    public function getSundayClose(): ?string;

    /**
     * Set sunday close
     *
     * @param string|null $sundayClose
     * @return void
     */
    public function setSundayClose(?string $sundayClose): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\ClickAndCollectApi\Api\Data\ScheduleExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Magestore\ClickAndCollectApi\Api\Data\ScheduleExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\ScheduleExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\ClickAndCollectApi\Api\Data\ScheduleExtensionInterface $extensionAttributes
    ): void;
}
