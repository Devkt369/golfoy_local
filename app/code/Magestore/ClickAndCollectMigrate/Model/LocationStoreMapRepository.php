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
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap as LocationStoreMapResourceModel;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap\Collection;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap\CollectionFactory;
use Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface;
use Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterfaceFactory;
use Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapSearchResultsInterface;
use Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapSearchResultsInterfaceFactory;
use Magestore\ClickAndCollectMigrate\Api\LocationStoreMapRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class LocationStoreMapRepository
 *
 * Use for CRUD Location Store Map
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class LocationStoreMapRepository implements LocationStoreMapRepositoryInterface
{
    /**
     * @var CollectionFactory
     */
    protected $locationStoreMapCollectionFactory;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var LocationStoreMapSearchResultsInterfaceFactory
     */
    protected $locationStoreMapSearchResultsInterfaceFactory;

    /**
     * @var LocationStoreMapResourceModel
     */
    protected $locationStoreMapResourceModel;

    /**
     * @var LocationStoreMapInterfaceFactory
     */
    protected $locationStoreMapInterfaceFactory;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * LocationStoreMapRepository constructor.
     *
     * @param CollectionFactory $locationStoreMapCollectionFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @param LocationStoreMapSearchResultsInterfaceFactory $locationStoreMapSearchResultsInterfaceFactory
     * @param LocationStoreMapResourceModel $locationStoreMapResourceModel
     * @param LocationStoreMapInterfaceFactory $locationStoreMapInterfaceFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $locationStoreMapCollectionFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor,
        LocationStoreMapSearchResultsInterfaceFactory $locationStoreMapSearchResultsInterfaceFactory,
        LocationStoreMapResourceModel $locationStoreMapResourceModel,
        LocationStoreMapInterfaceFactory $locationStoreMapInterfaceFactory,
        LoggerInterface $logger
    ) {
        $this->locationStoreMapCollectionFactory = $locationStoreMapCollectionFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
        $this->locationStoreMapSearchResultsInterfaceFactory = $locationStoreMapSearchResultsInterfaceFactory;
        $this->locationStoreMapResourceModel = $locationStoreMapResourceModel;
        $this->locationStoreMapInterfaceFactory = $locationStoreMapInterfaceFactory;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function save(LocationStoreMapInterface $locationStoreMap): LocationStoreMapInterface
    {
        try {
            $this->locationStoreMapResourceModel->save($locationStoreMap);
            return $locationStoreMap;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotSaveException(__('Could not save Location Store Map'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function get(int $storePickupId): LocationStoreMapInterface
    {
        /** @var LocationStoreMapInterface $locationStoreMap */
        $locationStoreMap = $this->locationStoreMapInterfaceFactory->create();
        $this->locationStoreMapResourceModel->load(
            $locationStoreMap,
            $storePickupId,
            LocationStoreMapInterface::STOREPICKUP_ID
        );

        if (!$locationStoreMap->getStorepickupId()) {
            throw new NoSuchEntityException(
                __(
                    'Location Store Map with id "%value" does not exist.',
                    ['value' => $storePickupId]
                )
            );
        }
        return $locationStoreMap;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $storePickupId): void
    {
        /** @var LocationStoreMapInterface $locationStoreMap */
        $locationStoreMap = $this->locationStoreMapInterfaceFactory->create();
        $this->locationStoreMapResourceModel->load(
            $locationStoreMap,
            $storePickupId,
            LocationStoreMapInterface::STOREPICKUP_ID
        );

        if (!$locationStoreMap->getStorepickupId()) {
            throw new NoSuchEntityException(
                __(
                    'There is no Location Store Map with "%fieldValue" for "%fieldName". Verify and try again.',
                    [
                        'fieldName' => LocationStoreMapInterface::STOREPICKUP_ID,
                        'fieldValue' => $storePickupId
                    ]
                )
            );
        }

        try {
            $this->locationStoreMapResourceModel->delete($locationStoreMap);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new CouldNotDeleteException(__('Could not delete Location Store Map'), $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria = null): LocationStoreMapSearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->locationStoreMapCollectionFactory->create();

        if (null === $searchCriteria) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($searchCriteria, $collection);
        }

        /** @var LocationStoreMapSearchResultsInterface $searchResult */
        $searchResult = $this->locationStoreMapSearchResultsInterfaceFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($searchCriteria);
        return $searchResult;
    }
}
