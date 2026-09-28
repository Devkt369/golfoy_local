<?php 
namespace ArtM\SSO\Model\ResourceModel\Sendotp;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(
            'ArtM\SSO\Model\Sendotp',
            'ArtM\SSO\Model\ResourceModel\Sendotp'
	    );
    }
}