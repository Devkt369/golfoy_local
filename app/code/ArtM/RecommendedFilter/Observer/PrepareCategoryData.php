<?php
namespace ArtM\RecommendedFilter\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;

class PrepareCategoryData implements ObserverInterface
{
    protected $_request;

    public function __construct(
        RequestInterface $request
    ) {
        $this->_request = $request;
    }
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
      /* $category = $observer->getCategory();
       print_r($category->getData()); 
       print_r($this->_request->getParams()); die;

        foreach ($category->getProductsPosition() as $productId => $position) {
       echo  $sortingPointsValue = (int)$this->_request->getParam('products')[$productId]['sorting_points'];
       
    }

    die;

        $sortingPointsData = $this->_request->getParam('sorting_points');
        if ($sortingPointsData !== null) {
            foreach ($sortingPointsData as $productId => $sortingPointsValue) {
                $productLink = $category->getProductLink($productId);
                if ($productLink !== null) {
                    $productLink->setData('sorting_points', $sortingPointsValue);
                    $productLink->save();
                }
            }
        } */
    }
}
