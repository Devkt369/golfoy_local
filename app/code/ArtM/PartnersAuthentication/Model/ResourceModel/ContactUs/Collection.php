<?php
namespace ArtM\PartnersAuthentication\Model\ResourceModel\ContactUs;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    public function _construct()
    {
        $this->_init('ArtM\PartnersAuthentication\Model\ContactUs', 'ArtM\PartnersAuthentication\Model\ResourceModel\ContactUs');
    }
}