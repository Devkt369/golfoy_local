<?php
namespace ArtM\Forms\Model\ResourceModel\Forms;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(\ArtM\Forms\Model\Forms::class, \ArtM\Forms\Model\ResourceModel\Forms::class);
    }
}
