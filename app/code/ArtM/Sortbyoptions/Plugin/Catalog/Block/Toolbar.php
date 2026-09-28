<?php
namespace ArtM\Sortbyoptions\Plugin\Catalog\Block;
use Magento\Framework\Data\Collection;
class Toolbar
{
    public function aroundSetCollection(\Magento\Catalog\Block\Product\ProductList\Toolbar $subject, 
    \Closure $proceed, $collection) 
    {
       $currentOrder = $subject->getCurrentOrder();
        $result = $proceed($collection);
        if($currentOrder)
        {
           switch ($currentOrder) {
                case 'p_high_to_low':
                    $subject->getCollection()->setOrder('customprice', 'desc');
                break;
                case 'p_low_to_high':
                    $subject->getCollection()->setOrder('customprice', 'asc');
                break;
                case 'crating':            
                    $subject->getCollection()->setOrder($currentOrder, 'desc')
                                             ->setOrder('cratingc', 'desc');                 
                break;
                case 'created':            
                    $subject->getCollection()->setOrder($currentOrder, 'desc');              
                break;
                case 'discount':            
                    $subject->getCollection()->setOrder($currentOrder, 'desc');              
                break;
                case 'crecommend':            
                    $subject->getCollection()->setOrder($currentOrder, 'desc');             
                break;
            }          
        }
        //echo $collection->getSelect()->__toString();
        // $subject->setDefaultOrder('discount');
        return $result;
    }    
   public function aroundGetAvailableOrders(
        \Magento\Catalog\Block\Product\ProductList\Toolbar $subject,
        \Closure $proceed
    ) {
        $result = $proceed();        
        if (array_key_exists('price', $result)) unset($result['price']);
        //if (array_key_exists('position', $result)) unset($result['position']);
        if (array_key_exists('name', $result)) unset($result['name']);
        //if (array_key_exists('personalized', $result)) unset($result['personalized']);
        if (array_key_exists('created_at', $result)) unset($result['created_at']);
        return $result;
    }
}