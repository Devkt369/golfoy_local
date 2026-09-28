<?php 
namespace ArtM\Loginbyphone\Model\ResourceModel\Getkeys;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\Loginbyphone\Model\Getkeys',
            'ArtM\Loginbyphone\Model\ResourceModel\Getkeys'
	    );
    }
}