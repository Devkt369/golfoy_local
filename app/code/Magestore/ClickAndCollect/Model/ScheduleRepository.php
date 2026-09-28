<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\ClickAndCollect\Model\ResourceModel\Schedule as ScheduleResourceModel;
use Magestore\ClickAndCollect\Model\ResourceModel\Schedule\Collection;
use Magestore\ClickAndCollect\Model\ResourceModel\Schedule\CollectionFactory;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleSearchResultsInterface;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleSearchResultsInterfaceFactory;
use Magestore\ClickAndCollectApi\Api\ScheduleRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ScheduleRepository
 *
 * Use for CRUD schedule
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ScheduleRepository implements ScheduleRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    protected $scheduleCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ScheduleSearchResultsInterfaceFactory
     */
    protected $scheduleSearchResultsInterfaceFactory;

    /**
     * @var ScheduleResourceModel
     */
    protected $scheduleResource;

    /**
     * @var ScheduleInterfaceFactory
     */
    protected $scheduleInterfaceFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * ScheduleRepository constructor.
     *
     * @param CollectionFactory $scheduleCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @param ScheduleSearchResultsInterfaceFactory $scheduleSearchResultsInterfaceFactory
     * @param ScheduleResourceModel $scheduleResource
     * @param ScheduleInterfaceFactory $scheduleInterfaceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $scheduleCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor,
        ScheduleSearchResultsInterfaceFactory $scheduleSearchResultsInterfaceFactory,
        ScheduleResourceModel $scheduleResource,
        ScheduleInterfaceFactory $scheduleInterfaceFactory,
        LoggerInterface $logger
    ) {
        $this->scheduleCollectionFactory = $scheduleCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
        $this->scheduleSearchResultsInterfaceFactory = $scheduleSearchResultsInterfaceFactory;
        $this->scheduleResource = $scheduleResource;
        $this->scheduleInterfaceFactory = $scheduleInterfaceFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function save(ScheduleInterface $schedule): ScheduleInterface
    {
        try {
            $this->scheduleResource->save($schedule);
            return $schedule;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save Schedule'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function get(int $scheduleId): ScheduleInterface
    {
        /** @var ScheduleInterface $source */
        $schedule = $this->scheduleInterfaceFactory->create();
        $this->scheduleResource->load($schedule, $scheduleId);

        if (null === $schedule->getScheduleId()) {
            throw new NoSuchEntityException(
                __(
                    'Schedule with id "%value" does not exist.',
                    ['value' => $scheduleId]
                )
            );
        }
        return $schedule;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $scheduleId): void
    {
        /** @var ScheduleInterface $schedule */
        $schedule = $this->scheduleInterfaceFactory->create();
        $this->scheduleResource->load($schedule, $scheduleId, ScheduleInterface::SCHEDULE_ID);

        if (!$schedule->getScheduleId()) {
            throw new NoSuchEntityException(
                __(
                    'There is no schedule with "%fieldValue" for "%fieldName". Verify and try again.',
                    [
                        'fieldName' => ScheduleInterface::SCHEDULE_ID,
                        'fieldValue' => $scheduleId
                    ]
                )
            );
        }

        try {
            $this->scheduleResource->delete($schedule);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete Schedule'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(?SearchCriteriaInterface $searchCriteria = null): ScheduleSearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->scheduleCollectionFactory->create();

        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $collection);
        }

        /** @var ScheduleSearchResultsInterface $searchResult */
        $searchResult = $this->scheduleSearchResultsInterfaceFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }
}
