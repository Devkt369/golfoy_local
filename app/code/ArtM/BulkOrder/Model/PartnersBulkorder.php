<?php 

namespace ArtM\BulkOrder\Model;

class PartnersBulkorder extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\BulkOrder\Model\ResourceModel\PartnersBulkorder');
    }
}

