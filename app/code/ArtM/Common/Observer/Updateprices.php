<?php
namespace ArtM\Common\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Catalog\Model\Product;


class Updateprices implements ObserverInterface
{
    protected $connection;
    protected $ProductModel;

    public function __construct(\Magento\Framework\App\ResourceConnection $resourceConnection, 
        Product $ProductModel) {  
        $this->connection   = $resourceConnection->getConnection();
        $this->ProductModel = $ProductModel;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {           
        $product   = $observer->getEvent()->getProduct();       
        $productId = $product->getId(); 
        if ($product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
        && $product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
        && $product->getTypeId() == \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE){            
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $_configChild = $product->getTypeInstance()->getUsedProductIds($product);
            
            if (!empty($_configChild)){

                //--------------------------------------
                $tmpparr = [];
                $tmpparrfull = [];
                foreach ($_configChild as $child){
                    $childProduct = $this->ProductModel->load( $child);
                    $price = (int) $childProduct->getPrice();
                    $specialPrice = (int) $childProduct->getFinalPrice();

                    $tmpparr[$childProduct->getId()] =  $specialPrice;
                    $tmpparrfull[$childProduct->getId()] =  [$price, $specialPrice];
                }
                asort($tmpparr);
                $minValue = reset($tmpparr);
                $minKey = array_keys($tmpparr)[0];

                $data = [           
                    'attribute_id' => 75,
                    'store_id'     => 0,
                    'entity_id'    => $productId,
                    'value'        => $tmpparrfull[$minKey][0]
                ]; 
                $this->prciesUpdateOnsave($data);  

                $data = [           
                    'attribute_id' => 76,
                    'store_id'     => 0,
                    'entity_id'    => $productId,
                    'value'        => $tmpparrfull[$minKey][1]
                ]; 
                $this->prciesUpdateOnsave($data);  

                //------------------------------------
                /*foreach ($_configChild as $child){
                    $childProduct = $this->ProductModel->load( $child);

                    $price = (int) $childProduct->getPrice();
                    if($price > 0){
                        $data = [           
                            'attribute_id' => 75,
                            'store_id'     => 0,
                            'entity_id'    => $productId,
                            'value'        => $price
                        ]; 
                        $this->prciesUpdateOnsave($data);  
                    }
                    $specialPrice = (int) $childProduct->getSpecialPrice();
                    //if($specialPrice > 0){
                        $data = [           
                            'attribute_id' => 76,
                            'store_id'     => 0,
                            'entity_id'    => $productId,
                            'value'        => $specialPrice
                        ]; 
                        $this->prciesUpdateOnsave($data);  
                    //}
                    break;
                } */
                ///////////////update gtin////////////////
                if ($product->getGtin() == ''){

              
                    foreach ($_configChild as $child){
                        $childProduct = $this->ProductModel->load( $child);

                            $gtin = $childProduct->getGtin();
                           
                            if($gtin != ''){
                                $datagtin = [           
                                    'attribute_id' => 592,
                                    'store_id'     => 0,
                                    'entity_id'    => $productId,
                                    'value'        => $gtin
                                ]; 
                                $this->gtinUpdateOnsave($datagtin);
                                break;
                            }
                    } 
                } 
            }               
        }
    }
    public function prciesUpdateOnsave($data){        
        
        $condition1 = $this->connection->quoteInto('entity_id = ?', $data['entity_id']);
        $condition2 = $this->connection->quoteInto('attribute_id = ?', $data['attribute_id']);
        $where = [$condition1 . ' AND ' . $condition2];

        $sql = $this->connection->select()->from('catalog_product_entity_decimal')->where(implode(' ', $where));
        $row = $this->connection->fetchOne($sql);
        if ($row) {
           $this->connection->update('catalog_product_entity_decimal', $data, $where);
        } else {
           $this->connection->insert('catalog_product_entity_decimal', $data);
        }
    } 

    public function gtinUpdateOnsave($datagtin){        
        
        $condition1 = $this->connection->quoteInto('entity_id = ?', $datagtin['entity_id']);
        $condition2 = $this->connection->quoteInto('attribute_id = ?', $datagtin['attribute_id']);
        $where = [$condition1 . ' AND ' . $condition2];

        $sql = $this->connection->select()->from('catalog_product_entity_varchar')->where(implode(' ', $where));
        $row = $this->connection->fetchOne($sql);
        if ($row) {
           $this->connection->update('catalog_product_entity_varchar', $datagtin, $where);
        } else {
           $this->connection->insert('catalog_product_entity_varchar', $datagtin);
        }
    } 

}       
       
        
