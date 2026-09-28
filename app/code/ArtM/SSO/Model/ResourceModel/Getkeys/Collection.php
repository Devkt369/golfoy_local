<?php 
namespace ArtM\SSO\Model\ResourceModel\Getkeys;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\SSO\Model\Getkeys',
            'ArtM\SSO\Model\ResourceModel\Getkeys'
	    );
    }
}