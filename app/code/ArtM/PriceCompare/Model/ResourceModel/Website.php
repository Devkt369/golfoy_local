<?php
namespace ArtM\PriceCompare\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Website extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('artm_pricecompare_website', 'website_id');
    }
}
