<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\ClickAndCollectMigrate\Api\Data\LogInterface;
use Magestore\ClickAndCollectMigrate\Api\Data\LogInterfaceFactory;
use Magestore\ClickAndCollectMigrate\Api\Data\LogSearchResultsInterface;
use Magestore\ClickAndCollectMigrate\Api\Data\LogSearchResultsInterfaceFactory;
use Magestore\ClickAndCollectMigrate\Api\LogRepositoryInterface;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\Log\Collection;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\Log\CollectionFactory;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\Log as LogResourceModel;

/**
 * Migrate log repository
 */
class LogRepository implements LogRepositoryInterface
{
    /**
     * @var LogInterfaceFactory
     */
    protected $logFactory;

    /**
     * @var LogResourceModel
     */
    protected $logResource;

    /**
     * @var LogSearchResultsInterfaceFactory
     */
    protected $logSearchResultsInterfaceFactory;

    /**
     * @var CollectionFactory
     */
    protected $logCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * LogRepository constructor.
     * @param LogInterfaceFactory $logFactory
     * @param LogResourceModel $logResource
     * @param CollectionFactory $logCollectionFactory
     * @param LogSearchResultsInterfaceFactory $logSearchResultsInterfaceFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     */
    public function __construct(
        LogInterfaceFactory $logFactory,
        LogResourceModel $logResource,
        CollectionFactory $logCollectionFactory,
        LogSearchResultsInterfaceFactory $logSearchResultsInterfaceFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->logFactory = $logFactory;
        $this->logResource = $logResource;
        $this->logSearchResultsInterfaceFactory = $logSearchResultsInterfaceFactory;
        $this->logCollectionFactory = $logCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(LogInterface $log)
    {
        try {
            $this->logResource->save($log);
            return $log;
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save log.'), $e);
        }
    }

    /**
     * @inheritDoc
     */
    public function getById(int $logId)
    {
        $log = $this->logFactory->create();
        $this->logResource->load($log, $logId);
        if (!$log->getId()) {
            throw new NoSuchEntityException(__('log with id "%1" does not exist.', $logId));
        } else {
            return $log;
        }
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var Collection $collection */
        $collection = $this->logCollectionFactory->create();

        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $collection);
        }

        /** @var LogSearchResultsInterface $searchResult */
        $searchResult = $this->logSearchResultsInterfaceFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }

    /**
     * @inheritdoc
     */
    public function getAlllog()
    {
        /** @var Collection $collection */
        $collection = $this->logCollectionFactory->create();
        return $collection;
    }
}
