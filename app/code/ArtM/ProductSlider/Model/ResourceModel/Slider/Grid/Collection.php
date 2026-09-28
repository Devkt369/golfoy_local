<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel\Slider\Grid;

use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;

class Collection extends SearchResult
{
    protected function _construct(): void
    {
        $this->_init('ArtM\\ProductSlider\\Model\\Slider', 'ArtM\\ProductSlider\\Model\\ResourceModel\\Slider');
        $this->setMainTable('artm_product_slider');
    }
}
