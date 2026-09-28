<?php 

namespace ArtM\InvoiceGrid\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class Invoices extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_invoices','id');
    }
}

?>