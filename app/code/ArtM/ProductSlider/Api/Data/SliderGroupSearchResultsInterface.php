<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SliderGroupSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \ArtM\ProductSlider\Api\Data\SliderGroupInterface[]
     */
    public function getItems();

    /**
     * @param \ArtM\ProductSlider\Api\Data\SliderGroupInterface[] $items
     */
    public function setItems(array $items);
}
