<?php
namespace ArtM\BulkOrder\Model\ResourceModel\PartnersBulkorder;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\BulkOrder\Model\PartnersBulkorder', 'ArtM\BulkOrder\Model\ResourceModel\PartnersBulkorder');
    }
}