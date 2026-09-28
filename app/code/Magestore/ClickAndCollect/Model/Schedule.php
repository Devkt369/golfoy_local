<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Magestore\ClickAndCollect\Model\ResourceModel\Schedule as ScheduleResourceModel;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleExtensionInterface;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;

/**
 * Class Schedule
 *
 * Used for schedule model
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Schedule extends AbstractExtensibleModel implements ScheduleInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(ScheduleResourceModel::class);
    }

    /**
     * @inheritdoc
     */
    public function getScheduleId(): ?int
    {
        return (int) $this->getData(self::SCHEDULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setScheduleId(?int $scheduleId): void
    {
        $this->setData(self::SCHEDULE_ID, $scheduleId);
    }

    /**
     * @inheritdoc
     */
    public function getScheduleName(): ?string
    {
        return $this->getData(self::SCHEDULE_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setScheduleName(?string $scheduleName): void
    {
        $this->setData(self::SCHEDULE_NAME, $scheduleName);
    }

    /**
     * @inheritdoc
     */
    public function getMondayStatus(): ?int
    {
        return (int) $this->getData(self::MONDAY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setMondayStatus(?int $mondayStatus): void
    {
        $this->setData(self::MONDAY_STATUS, $mondayStatus);
    }

    /**
     * @inheritdoc
     */
    public function getTuesdayStatus(): ?int
    {
        return (int) $this->getData(self::TUESDAY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setTuesdayStatus(?int $tuesdayStatus): void
    {
        $this->setData(self::TUESDAY_STATUS, $tuesdayStatus);
    }

    /**
     * @inheritdoc
     */
    public function getWednesdayStatus(): ?int
    {
        return (int) $this->getData(self::WEDNESDAY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setWednesdayStatus(?int $wednesdayStatus): void
    {
        $this->setData(self::WEDNESDAY_STATUS, $wednesdayStatus);
    }

    /**
     * @inheritdoc
     */
    public function getThursdayStatus(): ?int
    {
        return (int) $this->getData(self::THURSDAY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setThursdayStatus(?int $thursdayStatus): void
    {
        $this->setData(self::THURSDAY_STATUS, $thursdayStatus);
    }

    /**
     * @inheritdoc
     */
    public function getFridayStatus(): ?int
    {
        return (int) $this->getData(self::FRIDAY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setFridayStatus(?int $fridayStatus): void
    {
        $this->setData(self::FRIDAY_STATUS, $fridayStatus);
    }

    /**
     * @inheritdoc
     */
    public function getSaturdayStatus(): ?int
    {
        return (int) $this->getData(self::SATURDAY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setSaturdayStatus(?int $saturdayStatus): void
    {
        $this->setData(self::SATURDAY_STATUS, $saturdayStatus);
    }

    /**
     * @inheritdoc
     */
    public function getSundayStatus(): ?int
    {
        return (int) $this->getData(self::SUNDAY_STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setSundayStatus(?int $sundayStatus): void
    {
        $this->setData(self::SUNDAY_STATUS, $sundayStatus);
    }

    /**
     * @inheritdoc
     */
    public function getMondayOpen(): ?string
    {
        return $this->getData(self::MONDAY_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setMondayOpen(?string $mondayOpen): void
    {
        $this->setData(self::MONDAY_OPEN, $mondayOpen);
    }

    /**
     * @inheritdoc
     */
    public function getTuesdayOpen(): ?string
    {
        return $this->getData(self::TUESDAY_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setTuesdayOpen(?string $tuesdayOpen): void
    {
        $this->setData(self::TUESDAY_OPEN, $tuesdayOpen);
    }

    /**
     * @inheritdoc
     */
    public function getWednesdayOpen(): ?string
    {
        return $this->getData(self::WEDNESDAY_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setWednesdayOpen(?string $wednesdayOpen): void
    {
        $this->setData(self::WEDNESDAY_OPEN, $wednesdayOpen);
    }

    /**
     * @inheritdoc
     */
    public function getThursdayOpen(): ?string
    {
        return $this->getData(self::THURSDAY_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setThursdayOpen(?string $thursdayOpen): void
    {
        $this->setData(self::THURSDAY_OPEN, $thursdayOpen);
    }

    /**
     * @inheritdoc
     */
    public function getFridayOpen(): ?string
    {
        return $this->getData(self::FRIDAY_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setFridayOpen(?string $fridayOpen): void
    {
        $this->setData(self::FRIDAY_OPEN, $fridayOpen);
    }

    /**
     * @inheritdoc
     */
    public function getSaturdayOpen(): ?string
    {
        return $this->getData(self::SATURDAY_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setSaturdayOpen(?string $saturdayOpen): void
    {
        $this->setData(self::SATURDAY_OPEN, $saturdayOpen);
    }

    /**
     * @inheritdoc
     */
    public function getSundayOpen(): ?string
    {
        return $this->getData(self::SUNDAY_OPEN);
    }

    /**
     * @inheritdoc
     */
    public function setSundayOpen(?string $sundayOpen): void
    {
        $this->setData(self::SUNDAY_OPEN, $sundayOpen);
    }

    /**
     * @inheritdoc
     */
    public function getMondayClose(): ?string
    {
        return $this->getData(self::MONDAY_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setMondayClose(?string $mondayClose): void
    {
        $this->setData(self::MONDAY_CLOSE, $mondayClose);
    }

    /**
     * @inheritdoc
     */
    public function getTuesdayClose(): ?string
    {
        return $this->getData(self::TUESDAY_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setTuesdayClose(?string $tuesdayClose): void
    {
        $this->setData(self::TUESDAY_CLOSE, $tuesdayClose);
    }

    /**
     * @inheritdoc
     */
    public function getWednesdayClose(): ?string
    {
        return $this->getData(self::WEDNESDAY_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setWednesdayClose(?string $wednesdayClose): void
    {
        $this->setData(self::WEDNESDAY_CLOSE, $wednesdayClose);
    }

    /**
     * @inheritdoc
     */
    public function getThursdayClose(): ?string
    {
        return $this->getData(self::THURSDAY_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setThursdayClose(?string $thursdayClose): void
    {
        $this->setData(self::THURSDAY_CLOSE, $thursdayClose);
    }

    /**
     * @inheritdoc
     */
    public function getFridayClose(): ?string
    {
        return $this->getData(self::FRIDAY_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setFridayClose(?string $fridayClose): void
    {
        $this->setData(self::FRIDAY_CLOSE, $fridayClose);
    }

    /**
     * @inheritdoc
     */
    public function getSaturdayClose(): ?string
    {
        return $this->getData(self::SATURDAY_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setSaturdayClose(?string $saturdayClose): void
    {
        $this->setData(self::SATURDAY_CLOSE, $saturdayClose);
    }

    /**
     * @inheritdoc
     */
    public function getSundayClose(): ?string
    {
        return $this->getData(self::SUNDAY_CLOSE);
    }

    /**
     * @inheritdoc
     */
    public function setSundayClose(?string $sundayClose): void
    {
        $this->setData(self::SUNDAY_CLOSE, $sundayClose);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?ScheduleExtensionInterface
    {
        $extensionAttributes = $this->_getExtensionAttributes();
        if (null === $extensionAttributes) {
            $extensionAttributes = $this->extensionAttributesFactory->create(ScheduleInterface::class);
            $this->setExtensionAttributes($extensionAttributes);
        }
        return $extensionAttributes;
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(ScheduleExtensionInterface $extensionAttributes): void
    {
        $this->_setExtensionAttributes($extensionAttributes);
    }
}
