<?php
namespace ArtM\CustomProductDesign\Model\ResourceModel\Area;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\CustomProductDesign\Model\Area',
            'ArtM\CustomProductDesign\Model\ResourceModel\Area'
        );
    }
}
