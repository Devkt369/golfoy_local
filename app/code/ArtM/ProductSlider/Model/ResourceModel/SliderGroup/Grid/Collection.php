<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel\SliderGroup\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _construct(): void
    {
        $this->_init('ArtM\\ProductSlider\\Model\\SliderGroup', 'ArtM\\ProductSlider\\Model\\ResourceModel\\SliderGroup');
        $this->setMainTable('artm_product_slider_group');
    }
}
