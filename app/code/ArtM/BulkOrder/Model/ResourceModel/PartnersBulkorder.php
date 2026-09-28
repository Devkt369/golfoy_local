<?php 

namespace ArtM\BulkOrder\Model\ResourceModel;
class PartnersBulkorder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_bulkorder_partner','id');
    }
}

?>