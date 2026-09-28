<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Data;

/**
 * Represents Special Day Interface
 *
 * @api
 */
interface SpecialDayInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const SPECIAL_DAY_ID = 'specialday_id';
    const SPECIAL_DAY_NAME = 'specialday_name';
    const STORE_STATUS = 'store_status';
    const REPEAT = 'repeat';
    const DATE_FROM = 'date_from';
    const DATE_TO = 'date_to';
    const TIME_OPEN = 'time_open';
    const TIME_CLOSE = 'time_close';

    /**
     * Constants for repeat
     */
    const REPEAT_NONE = 0;
    const REPEAT_YEARLY = 1;
    /**#@-*/

    /**
     * Get Special Day id
     *
     * @return int|null
     */
    public function getSpecialDayId(): ?int;

    /**
     * Set Special Day id
     *
     * @param int|null $specialDayId
     * @return void
     */
    public function setSpecialDayId(?int $specialDayId): void;

    /**
     * Get Special Day name
     *
     * @return string|null
     */
    public function getSpecialDayName(): ?string;

    /**
     * Set Special Day name
     *
     * @param string|null $specialDayName
     * @return void
     */
    public function setSpecialDayName(?string $specialDayName): void;

    /**
     * Get store status
     *
     * @return int|null
     */
    public function getStoreStatus(): ?int;

    /**
     * Set store status
     *
     * @param int|null $storeStatus
     * @return void
     */
    public function setStoreStatus(?int $storeStatus): void;

    /**
     * Get repeat
     *
     * @return int|null
     */
    public function getRepeat(): ?int;

    /**
     * Set repeat
     *
     * @param int|null $repeat
     * @return void
     */
    public function setRepeat(?int $repeat): void;

    /**
     * Get date from
     *
     * @return string|null
     */
    public function getDateFrom(): ?string;

    /**
     * Set data from
     *
     * @param string|null $dateFrom
     * @return void
     */
    public function setDateFrom(?string $dateFrom): void;

    /**
     * Get date to
     *
     * @return string|null
     */
    public function getDateTo(): ?string;

    /**
     * Set data to
     *
     * @param string|null $dateTo
     * @return void
     */
    public function setDateTo(?string $dateTo): void;

    /**
     * Get time open
     *
     * @return string|null
     */
    public function getTimeOpen(): ?string;

    /**
     * Set data to
     *
     * @param string|null $timeOpen
     * @return void
     */
    public function setTimeOpen(?string $timeOpen): void;

    /**
     * Get time close
     *
     * @return string|null
     */
    public function getTimeClose(): ?string;

    /**
     * Set time close
     *
     * @param string|null $timeClose
     * @return void
     */
    public function setTimeClose(?string $timeClose): void;

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\ClickAndCollectApi\Api\Data\SpecialDayExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Magestore\ClickAndCollectApi\Api\Data\SpecialDayExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\SpecialDayExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\ClickAndCollectApi\Api\Data\SpecialDayExtensionInterface $extensionAttributes
    ): void;
}
