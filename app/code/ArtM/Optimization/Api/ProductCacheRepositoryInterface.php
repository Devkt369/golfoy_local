<?php
namespace ArtM\Optimization\Api;

use ArtM\Optimization\Api\Data\ProductCacheInterface;

interface ProductCacheRepositoryInterface
{
    public function save(ProductCacheInterface $productCache);
    
    /**
     * Get id cache data by  ID.
     *
     * @param int $Id
     * @return ProductCacheInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    public function delete(ProductCacheInterface $productCache);

    public function deleteById($id);

    /**
     * Get product cache data by product ID.
     *
     * @param int $productId
     * @return ProductCacheInterface
     * @throws NoSuchEntityException
     */
    public function getByProductId($productId);

    /**
     * Get all product cache data 
     *
     * @return ProductCacheInterface
     */
    public function getAllData();
}
