<?php
namespace ArtM\Optimization\Observer\Catalog;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use ArtM\Optimization\Api\ProductCacheRepositoryInterface;

class ProductSaveAfter implements ObserverInterface
{
    protected $productCacheRepository;
    
    public function __construct(
        ProductCacheRepositoryInterface $productCacheRepository,
        ){
        $this->productCacheRepository = $productCacheRepository;
    }
    public function execute(Observer $observer)
    {
        try{
            $product = $observer->getEvent()->getProduct();
            $productId = $product->getId();
            $productCache = $this->productCacheRepository->getByProductId($productId);
            $this->productCacheRepository->delete($productCache);
        }catch(\Magento\Framework\Exception\NoSuchEntityException $e){
            
        }
    }
}