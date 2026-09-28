<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface QuestionOptionSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \ArtM\CustomerSurvey\Api\Data\QuestionOptionInterface[]
     */
    public function getItems();

    /**
     * @param \ArtM\CustomerSurvey\Api\Data\QuestionOptionInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
