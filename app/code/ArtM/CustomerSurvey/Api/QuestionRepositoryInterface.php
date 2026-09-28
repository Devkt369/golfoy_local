<?php

namespace ArtM\CustomerSurvey\Api;

use ArtM\CustomerSurvey\Api\Data\QuestionInterface;
use ArtM\CustomerSurvey\Api\Data\QuestionSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface QuestionRepositoryInterface
{
    /**
     * @param int $questionId
     * @return QuestionInterface
     */
    public function getById($questionId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return QuestionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param QuestionInterface $question
     * @return QuestionInterface
     */
    public function save(QuestionInterface $question);

    /**
     * @param QuestionInterface $question
     * @return bool
     */
    public function delete(QuestionInterface $question);

    /**
     * @param int $questionId
     * @return bool
     */
    public function deleteById($questionId);
}
