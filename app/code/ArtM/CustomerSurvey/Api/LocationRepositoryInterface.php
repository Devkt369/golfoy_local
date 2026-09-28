<?php

namespace ArtM\CustomerSurvey\Api;

use ArtM\CustomerSurvey\Api\Data\LocationInterface;
use ArtM\CustomerSurvey\Api\Data\LocationSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

interface LocationRepositoryInterface
{
    /**
     * @param int $locationId
     * @return LocationInterface
     */
    public function getById($locationId);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return LocationSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * @param LocationInterface $location
     * @return LocationInterface
     */
    public function save(LocationInterface $location);

    /**
     * @param LocationInterface $location
     * @return bool
     */
    public function delete(LocationInterface $location);

    /**
     * @param int $locationId
     * @return bool
     */
    public function deleteById($locationId);
}
