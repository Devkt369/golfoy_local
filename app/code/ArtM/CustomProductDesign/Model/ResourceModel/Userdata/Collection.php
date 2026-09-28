<?php
namespace ArtM\CustomProductDesign\Model\ResourceModel\Userdata;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\CustomProductDesign\Model\Userdata',
            'ArtM\CustomProductDesign\Model\ResourceModel\Userdata'
        );
    }
}
