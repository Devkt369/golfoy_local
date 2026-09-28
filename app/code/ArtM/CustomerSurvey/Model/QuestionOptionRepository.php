<?php

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Api\Data\QuestionOptionInterface;
use ArtM\CustomerSurvey\Api\Data\QuestionOptionSearchResultsInterfaceFactory;
use ArtM\CustomerSurvey\Api\QuestionOptionRepositoryInterface;
use ArtM\CustomerSurvey\Model\ResourceModel\QuestionOption as OptionResource;
use ArtM\CustomerSurvey\Model\ResourceModel\QuestionOption\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class QuestionOptionRepository implements QuestionOptionRepositoryInterface
{
    /**
     * @var QuestionOptionFactory
     */
    private $optionFactory;

    /**
     * @var OptionResource
     */
    private $resource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var QuestionOptionSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        QuestionOptionFactory $optionFactory,
        OptionResource $resource,
        CollectionFactory $collectionFactory,
        QuestionOptionSearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->optionFactory = $optionFactory;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function getById($optionId)
    {
        $option = $this->optionFactory->create();
        $this->resource->load($option, $optionId);
        if (!$option->getId()) {
            throw new NoSuchEntityException(__('Question option with id "%1" does not exist.', $optionId));
        }

        return $option;
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

    public function save(QuestionOptionInterface $option)
    {
        try {
            $this->resource->save($option);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save question option: %1', $exception->getMessage()),
                $exception
            );
        }

        return $option;
    }

    public function delete(QuestionOptionInterface $option)
    {
        try {
            $this->resource->delete($option);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete question option: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    public function deleteById($optionId)
    {
        return $this->delete($this->getById($optionId));
    }
}
