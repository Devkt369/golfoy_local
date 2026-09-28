<?php 
    namespace ArtM\CustomPrinting\Model\ResourceModel;
    class Customprinting extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
        public function _construct(){
            $this->_init("artm_customprint","id");
        }
    }
?>
