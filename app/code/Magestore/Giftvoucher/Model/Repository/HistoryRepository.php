<?php
/**
 * Copyright © 2017 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Giftvoucher\Model\Repository;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magestore\Giftvoucher\Api\Data\HistoryInterface;
use Magestore\Giftvoucher\Api\Data\HistoryInterfaceFactory;
use Magestore\Giftvoucher\Api\Data\HistorySearchResultsInterface;
use Magestore\Giftvoucher\Api\Data\HistorySearchResultsInterfaceFactory;
use Magestore\Giftvoucher\Api\HistoryRepositoryInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magestore\Giftvoucher\Model\HistoryFactory as HistoryFactory;
use Magestore\Giftvoucher\Model\ResourceModel\History as ResourceModel;
use Magestore\Giftvoucher\Model\ResourceModel\History\CollectionFactory as CollectionFactory;
use Magestore\Giftvoucher\Model\ResourceModel\History\Collection as HistoryCollection;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Giftvoucher - HistoryRepository
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class HistoryRepository implements HistoryRepositoryInterface
{
    /**
     * @var ResourceModel
     */
    protected $resource;

    /**
     * @var HistoryFactory
     */
    protected $modelFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var HistorySearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var HistoryInterfaceFactory
     */
    protected $dataModelFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * HistoryRepository constructor.
     * @param ResourceModel $resource
     * @param HistoryFactory $modelFactory
     * @param HistorySearchResultsInterfaceFactory $dataModelFactory
     * @param CollectionFactory $collectionFactory
     * @param HistorySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionProcessorInterface $collectionProcessor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ResourceModel $resource,
        HistoryFactory $modelFactory,
        HistorySearchResultsInterfaceFactory $dataModelFactory,
        CollectionFactory $collectionFactory,
        HistorySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->resource = $resource;
        $this->modelFactory = $modelFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataModelFactory = $dataModelFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->collectionProcessor = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(HistoryInterface $history)
    {
        try {
            $this->resource->save($history);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }
        return $history;
    }

    /**
     * @inheritDoc
     */
    public function getById($id)
    {
        $item = $this->modelFactory->create();
        $this->resource->load($item, $id);
        if (!$item->getId()) {
            throw new NoSuchEntityException(__('Gift Card with id "%1" does not exist.', $id));
        }
        return $item;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        /** @var HistoryCollection $collection */
        $collection = $this->collectionFactory->create();

        if (null === $criteria) {
            $criteria = $this->searchCriteriaBuilder->create();
        } else {
            $this->collectionProcessor->process($criteria, $collection);
        }

        /** @var HistorySearchResultsInterface $searchResult */
        $searchResult = $this->searchResultsFactory->create();
        $searchResult->setItems($collection->getItems());
        $searchResult->setTotalCount($collection->getSize());
        $searchResult->setSearchCriteria($criteria);
        return $searchResult;
    }
}
