<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SliderGroupItem extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('artm_product_slider_group_items', 'item_id');
    }
}
