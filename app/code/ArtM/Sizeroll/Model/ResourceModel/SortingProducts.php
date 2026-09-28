<?php
namespace ArtM\Sizeroll\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class SortingProducts extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_sorting_products', 'id');
    }
}
