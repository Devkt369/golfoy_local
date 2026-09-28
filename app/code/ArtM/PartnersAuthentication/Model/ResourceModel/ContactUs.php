<?php 

namespace ArtM\PartnersAuthentication\Model\ResourceModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
class ContactUs extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function _construct()
    {
        $this->_init('artm_dealer_contact_us','id');
    }
}

?>