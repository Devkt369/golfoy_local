<?php 
    namespace ArtM\ReservedProducts\Model\ResourceModel;
    use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
    class ReservedProducts extends AbstractDb
    {
        protected function _construct()
        {
            $this->_init("inventory_reservation","reservation_id");
        }
    }

