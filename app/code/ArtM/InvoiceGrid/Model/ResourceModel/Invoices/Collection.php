<?php
namespace ArtM\InvoiceGrid\Model\ResourceModel\Invoices;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\InvoiceGrid\Model\Invoices', 'ArtM\InvoiceGrid\Model\ResourceModel\Invoices');
    }
}