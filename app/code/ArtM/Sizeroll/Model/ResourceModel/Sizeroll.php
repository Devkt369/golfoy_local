<?php 
    namespace ArtM\Sizeroll\Model\ResourceModel;
    class Sizeroll extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
        public function _construct(){
            $this->_init("artm_sizeroll","id");
        }
    }
?>
