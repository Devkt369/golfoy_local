<?php 

namespace ArtM\BulkOrder\Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Bulkorder extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\BulkOrder\Model\ResourceModel\Bulkorder');
    }
}

