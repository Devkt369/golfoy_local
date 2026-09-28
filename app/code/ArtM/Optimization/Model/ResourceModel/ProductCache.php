<?php 
namespace ArtM\Optimization\Model\ResourceModel;
class ProductCache extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb{
    public function _construct(){
        $this->_init("artm_product_cache","id");
    }
}
?>
