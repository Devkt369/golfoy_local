<?php

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Api\Data\LocationInterface;
use ArtM\CustomerSurvey\Api\Data\LocationSearchResultsInterfaceFactory;
use ArtM\CustomerSurvey\Api\LocationRepositoryInterface;
use ArtM\CustomerSurvey\Model\ResourceModel\Location as LocationResource;
use ArtM\CustomerSurvey\Model\ResourceModel\Location\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class LocationRepository implements LocationRepositoryInterface
{
    /**
     * @var LocationFactory
     */
    private $locationFactory;

    /**
     * @var LocationResource
     */
    private $resource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var LocationSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        LocationFactory $locationFactory,
        LocationResource $resource,
        CollectionFactory $collectionFactory,
        LocationSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->locationFactory = $locationFactory;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function getById($locationId)
    {
        $location = $this->locationFactory->create();
        $this->resource->load($location, $locationId);
        if (!$location->getId()) {
            throw new NoSuchEntityException(__('Location with id "%1" does not exist.', $locationId));
        }

        return $location;
    }

    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    public function save(LocationInterface $location)
    {
        try {
            $this->resource->save($location);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save location: %1', $exception->getMessage()),
                $exception
            );
        }

        return $location;
    }

    public function delete(LocationInterface $location)
    {
        try {
            $this->resource->delete($location);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete location: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    public function deleteById($locationId)
    {
        return $this->delete($this->getById($locationId));
    }
}
