<?php 
namespace ArtM\Loginbyphone\Model\ResourceModel\Sendotp;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\Loginbyphone\Model\Sendotp',
            'ArtM\Loginbyphone\Model\ResourceModel\Sendotp'
	    );
    }
}