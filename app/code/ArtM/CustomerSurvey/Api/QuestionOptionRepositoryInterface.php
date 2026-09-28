<?php

namespace ArtM\CustomerSurvey\Api;

use ArtM\CustomerSurvey\Api\Data\QuestionOptionInterface;
use ArtM\CustomerSurvey\Api\Data\QuestionOptionSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface QuestionOptionRepositoryInterface
{
    /**
     * @param int $optionId
     * @return QuestionOptionInterface
     */
    public function getById($optionId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return QuestionOptionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param QuestionOptionInterface $option
     * @return QuestionOptionInterface
     */
    public function save(QuestionOptionInterface $option);

    /**
     * @param QuestionOptionInterface $option
     * @return bool
     */
    public function delete(QuestionOptionInterface $option);

    /**
     * @param int $optionId
     * @return bool
     */
    public function deleteById($optionId);
}
