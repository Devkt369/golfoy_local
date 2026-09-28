<?php 

namespace ArtM\BulkOrder\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class Bulkorder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_bulkorder','id');
    }
}

?>