<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface LocationSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * @return \ArtM\CustomerSurvey\Api\Data\LocationInterface[]
     */
    public function getItems();

    /**
     * @param \ArtM\CustomerSurvey\Api\Data\LocationInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
