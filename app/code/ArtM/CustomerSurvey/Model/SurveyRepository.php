<?php

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Api\Data\SurveyInterface;
use ArtM\CustomerSurvey\Api\Data\SurveySearchResultsInterfaceFactory;
use ArtM\CustomerSurvey\Api\SurveyRepositoryInterface;
use ArtM\CustomerSurvey\Model\ResourceModel\Survey as SurveyResource;
use ArtM\CustomerSurvey\Model\ResourceModel\Survey\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SurveyRepository implements SurveyRepositoryInterface
{
    /**
     * @var SurveyFactory
     */
    private $surveyFactory;

    /**
     * @var SurveyResource
     */
    private $resource;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SurveySearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    public function __construct(
        SurveyFactory $surveyFactory,
        SurveyResource $resource,
        CollectionFactory $collectionFactory,
        SurveySearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor
    ) {
        $this->surveyFactory = $surveyFactory;
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
    }

    public function getById($surveyId)
    {
        $survey = $this->surveyFactory->create();
        $this->resource->load($survey, $surveyId);
        if (!$survey->getId()) {
            throw new NoSuchEntityException(__('Survey with id "%1" does not exist.', $surveyId));
        }

        return $survey;
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

    public function save(SurveyInterface $survey)
    {
        try {
            $this->resource->save($survey);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save survey: %1', $exception->getMessage()),
                $exception
            );
        }

        return $survey;
    }

    public function delete(SurveyInterface $survey)
    {
        try {
            $this->resource->delete($survey);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete survey: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    public function deleteById($surveyId)
    {
        return $this->delete($this->getById($surveyId));
    }
}
