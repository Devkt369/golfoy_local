<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Model\DataProvider\Store\View;

use Magento\Framework\DataObject;
use Magestore\ClickAndCollectApi\Api\ScheduleRepositoryInterface;
use Magestore\Webpos\Api\Data\Location\LocationInterface;
use Psr\Log\LoggerInterface;

/**
 * Current viewing store data provider
 */
class CurrentStoreProvider extends DataObject
{
    const CURRENT_STORE = 'current_store';

    /**
     * @var ScheduleRepositoryInterface
     */
    protected $scheduleRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * CurrentStoreProvider constructor.
     * @param ScheduleRepositoryInterface $scheduleRepository
     * @param LoggerInterface $logger
     * @param array $data
     */
    public function __construct(
        ScheduleRepositoryInterface $scheduleRepository,
        LoggerInterface $logger,
        array $data = []
    ) {
        parent::__construct($data);
        $this->scheduleRepository = $scheduleRepository;
        $this->logger = $logger;
    }

    /**
     * Set Current Store
     *
     * @param LocationInterface $store
     */
    public function setCurrentStore(LocationInterface $store)
    {
        $schedule = null;
        if ($store->getScheduleId()) {
            try {
                $schedule = $this->scheduleRepository->get((int)$store->getScheduleId());
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
        $store->setSchedule($schedule);
        $this->setData(self::CURRENT_STORE, $store);
    }

    /**
     * Get Current Store
     *
     * @return LocationInterface|null
     */
    public function getCurrentStore()
    {
        return $this->getData(self::CURRENT_STORE);
    }
}
