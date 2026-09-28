<?php 

namespace ArtM\InvoiceGrid\Model;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Invoices extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('ArtM\InvoiceGrid\Model\ResourceModel\Invoices');
    }
}

