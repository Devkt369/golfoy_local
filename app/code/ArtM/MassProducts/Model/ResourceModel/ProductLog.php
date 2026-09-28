<?php
namespace ArtM\MassProducts\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class ProductLog extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_massproducts_product_log', 'entity_id');
    }
}
