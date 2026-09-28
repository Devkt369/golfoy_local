<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\Migrate;

use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface as CcScheduleInterface;
use Magestore\ClickAndCollectMigrate\Api\LocationStoreMapRepositoryInterface;
use Magestore\ClickAndCollectMigrate\Api\Migrate\MigrateSpecialDayServiceInterface;
use Magestore\Storepickup\Model\ResourceModel\Specialday\Collection as SpSpecialdayCollection;
use Magestore\Storepickup\Model\ResourceModel\Specialday\CollectionFactory as SpSpecialdayCollectionFactory;
use Magestore\Storepickup\Model\ResourceModel\Holiday\Collection as SpHolidayCollection;
use Magestore\Storepickup\Model\ResourceModel\Holiday\CollectionFactory as SpHolidayCollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterfaceFactory as CcSpecialDayInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface as CcSpecialDayInterface;
use Magestore\ClickAndCollectApi\Api\SpecialDayRepositoryInterface as CcSpecialDayRepositoryInterface;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterface;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterfaceFactory;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink\SaveMultiple
    as LocationSpecialDayLinkSaveMultiple;
use Psr\Log\LoggerInterface;

/**
 * Service Migrate Special Day
 */
class MigrateSpecialDayService implements MigrateSpecialDayServiceInterface
{
    /**
     * @var LocationStoreMapRepositoryInterface
     */
    protected $locationStoreMapRepository;

    /**
     * @var SpSpecialdayCollectionFactory
     */
    protected $spSpecialDayCollectionFactory;

    /**
     * @var SpHolidayCollectionFactory
     */
    protected $spHolidayCollectionFactory;

    /**
     * @var CcSpecialDayInterfaceFactory
     */
    protected $ccSpecialDayInterfaceFactory;

    /**
     * @var CcSpecialDayRepositoryInterface
     */
    protected $ccSpecialDayRepository;

    /**
     * @var LocationSpecialDayLinkInterfaceFactory
     */
    protected $locationSpecialDayLinkInterfaceFactory;

    /**
     * @var LocationSpecialDayLinkSaveMultiple
     */
    protected $locationSpecialDayLinkSaveMultiple;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * MigrateSpecialDayService constructor.
     *
     * @param LocationStoreMapRepositoryInterface $locationStoreMapRepository
     * @param SpSpecialdayCollectionFactory $spSpecialDayCollectionFactory
     * @param SpHolidayCollectionFactory $spHolidayCollectionFactory
     * @param CcSpecialDayInterfaceFactory $ccSpecialDayInterfaceFactory
     * @param CcSpecialDayRepositoryInterface $ccSpecialDayRepository
     * @param LocationSpecialDayLinkInterfaceFactory $locationSpecialDayLinkInterfaceFactory
     * @param LocationSpecialDayLinkSaveMultiple $locationSpecialDayLinkSaveMultiple
     * @param LoggerInterface $logger
     */
    public function __construct(
        LocationStoreMapRepositoryInterface $locationStoreMapRepository,
        SpSpecialdayCollectionFactory $spSpecialDayCollectionFactory,
        SpHolidayCollectionFactory $spHolidayCollectionFactory,
        CcSpecialDayInterfaceFactory $ccSpecialDayInterfaceFactory,
        CcSpecialDayRepositoryInterface $ccSpecialDayRepository,
        LocationSpecialDayLinkInterfaceFactory $locationSpecialDayLinkInterfaceFactory,
        LocationSpecialDayLinkSaveMultiple $locationSpecialDayLinkSaveMultiple,
        LoggerInterface $logger
    ) {
        $this->locationStoreMapRepository = $locationStoreMapRepository;
        $this->spSpecialDayCollectionFactory = $spSpecialDayCollectionFactory;
        $this->spHolidayCollectionFactory = $spHolidayCollectionFactory;
        $this->ccSpecialDayInterfaceFactory = $ccSpecialDayInterfaceFactory;
        $this->ccSpecialDayRepository = $ccSpecialDayRepository;
        $this->locationSpecialDayLinkInterfaceFactory = $locationSpecialDayLinkInterfaceFactory;
        $this->locationSpecialDayLinkSaveMultiple = $locationSpecialDayLinkSaveMultiple;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function migrate()
    {
        // Migrate data from Store pickup's special day
        $this->migrateFromSpecialDay();
        // Migrate data from Store pickup's holiday
        $this->migrateFromHoliday();
    }

    /**
     * @inheritDoc
     */
    public function migrateFromSpecialDay()
    {
        /** @var SpSpecialdayCollection $spSpecialDayCollection */
        $spSpecialDayCollection = $this->spSpecialDayCollectionFactory->create();
        foreach ($spSpecialDayCollection->getItems() as $spSpecialDay) {
            /** @var CcSpecialDayInterface $ccSpecialDay */
            $ccSpecialDay = $this->ccSpecialDayInterfaceFactory->create();
            $ccSpecialDay->setSpecialDayName($spSpecialDay->getSpecialdayName());
            $ccSpecialDay->setStoreStatus(CcScheduleInterface::OPEN_STATUS);
            $ccSpecialDay->setRepeat(CcSpecialDayInterface::REPEAT_NONE);
            $ccSpecialDay->setDateFrom($spSpecialDay->getDateFrom());
            $ccSpecialDay->setDateTo($spSpecialDay->getDateTo());
            $ccSpecialDay->setTimeOpen($spSpecialDay->getTimeOpen());
            $ccSpecialDay->setTimeClose($spSpecialDay->getTimeClose());

            $this->ccSpecialDayRepository->save($ccSpecialDay);

            $this->migrateLink($ccSpecialDay->getSpecialDayId(), $spSpecialDay->getStorepickupIds());
        }
    }

    /**
     * @inheritDoc
     */
    public function migrateFromHoliday()
    {
        /** @var SpHolidayCollection $spHolidayCollection */
        $spHolidayCollection = $this->spHolidayCollectionFactory->create();
        foreach ($spHolidayCollection->getItems() as $spHoliday) {
            /** @var CcSpecialDayInterface $ccSpecialDay */
            $ccSpecialDay = $this->ccSpecialDayInterfaceFactory->create();
            $ccSpecialDay->setSpecialDayName($spHoliday->getHolidayName());
            $ccSpecialDay->setStoreStatus(CcScheduleInterface::CLOSE_STATUS);
            $ccSpecialDay->setRepeat(CcSpecialDayInterface::REPEAT_NONE);
            $ccSpecialDay->setDateFrom($spHoliday->getDateFrom());
            $ccSpecialDay->setDateTo($spHoliday->getDateTo());
            $ccSpecialDay->setTimeOpen("00:00");
            $ccSpecialDay->setTimeClose("00:00");

            $this->ccSpecialDayRepository->save($ccSpecialDay);

            $this->migrateLink($ccSpecialDay->getSpecialDayId(), $spHoliday->getStorepickupIds());
        }
    }

    /**
     * Migrate Link
     *
     * @param int $specialDayId
     * @param array $storeIds
     */
    public function migrateLink(int $specialDayId, array $storeIds = [])
    {
        $locationSpecialDayLinks = [];
        foreach ($storeIds as $storeId) {
            try {
                $locationStoreMap = $this->locationStoreMapRepository->get((int)$storeId);
            } catch (\Exception $e) {
                $this->logger->debug($e->getMessage());
                continue;
            }
            /** @var LocationSpecialDayLinkInterface $locationSpecialDayLink */
            $locationSpecialDayLink = $this->locationSpecialDayLinkInterfaceFactory->create();
            $locationSpecialDayLink->setLocationId($locationStoreMap->getLocationId());
            $locationSpecialDayLink->setSpecialDayId($specialDayId);
            $locationSpecialDayLinks[] = $locationSpecialDayLink;
        }
        $this->locationSpecialDayLinkSaveMultiple->execute($locationSpecialDayLinks);
    }
}
