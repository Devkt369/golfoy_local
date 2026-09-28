<?php
namespace ArtM\PriceCompare\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Product extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_pricecompare_product', 'product_id');
    }
}
