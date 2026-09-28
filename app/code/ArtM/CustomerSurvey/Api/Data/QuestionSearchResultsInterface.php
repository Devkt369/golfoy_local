<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface QuestionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \ArtM\CustomerSurvey\Api\Data\QuestionInterface[]
     */
    public function getItems();

    /**
     * @param \ArtM\CustomerSurvey\Api\Data\QuestionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
