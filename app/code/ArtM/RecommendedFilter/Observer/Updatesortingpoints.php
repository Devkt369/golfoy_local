<?php
namespace ArtM\RecommendedFilter\Observer;

use Magento\Framework\Event\ObserverInterface;
use ArtM\RecommendedFilter\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable as ConfigurableType;


class Updatesortingpoints implements ObserverInterface
{
    protected $helper;

    public function __construct(
        Data $helper, ProductRepositoryInterface $productRepository, ConfigurableType $configurableType
    ) {
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->configurableType = $configurableType;
    }
    public function execute(\Magento\Framework\Event\Observer $observer)
    {           
        $product   = $observer->getEvent()->getProduct();       
        $productId = $product->getId();         
       // $product = $this->productRepository->getById($productId);       
        $attrid = $product->getAttributeSetId();
        

        //if product is not a child
        if ($product->getVisibility() == \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
        && $product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED){           
           /* $pts = $this->helper->gettotalpoints($productId);
            $insertArray = [$productId => $pts];  
            $this->helper->insertPoints($attrid, $productId);
            $this->helper->insertProcessedProducts($attrid, $productId);      
            $this->helper->updateCategoryProductsPoints($insertArray);*/
            $this->helper->UpdatePoints( $productId, 'observer' );
        }elseif($product->getVisibility() == '1' //if product is a child
        && $product->getStatus() == \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED){
            $parentIds = $this->configurableType->getParentIdsByChild($productId);
            if(is_array($parentIds) && (!empty($parentIds))){
                $parentId = array_shift($parentIds);
               /* $pts = $this->helper->gettotalpoints($parentId);
                $insertArray = [$parentId => $pts];  
                $this->helper->insertPoints($attrid, $parentId);
                $this->helper->insertProcessedProducts($attrid, $parentId);      
                $this->helper->updateCategoryProductsPoints($insertArray);*/
                $this->helper->UpdatePoints( $parentId, 'observer' );
            }
        }
    }
}
