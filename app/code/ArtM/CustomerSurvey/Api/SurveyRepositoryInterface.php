<?php

namespace ArtM\CustomerSurvey\Api;

use ArtM\CustomerSurvey\Api\Data\SurveyInterface;
use ArtM\CustomerSurvey\Api\Data\SurveySearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface SurveyRepositoryInterface
{
    /**
     * @param int $surveyId
     * @return SurveyInterface
     */
    public function getById($surveyId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return SurveySearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param SurveyInterface $survey
     * @return SurveyInterface
     */
    public function save(SurveyInterface $survey);

    /**
     * @param SurveyInterface $survey
     * @return bool
     */
    public function delete(SurveyInterface $survey);

    /**
     * @param int $surveyId
     * @return bool
     */
    public function deleteById($surveyId);
}
