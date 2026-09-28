<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SliderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * @return \ArtM\ProductSlider\Api\Data\SliderInterface[]
     */
    public function getItems();

    /**
     * @param \ArtM\ProductSlider\Api\Data\SliderInterface[] $items
     */
    public function setItems(array $items);
}
