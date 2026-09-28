<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface SurveySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \ArtM\CustomerSurvey\Api\Data\SurveyInterface[]
     */
    public function getItems();

    /**
     * @param \ArtM\CustomerSurvey\Api\Data\SurveyInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
