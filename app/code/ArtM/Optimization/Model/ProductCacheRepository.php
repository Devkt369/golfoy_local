<?php
namespace ArtM\Optimization\Model;

use ArtM\Optimization\Api\ProductCacheRepositoryInterface;
use ArtM\Optimization\Api\Data\ProductCacheInterface;
use ArtM\Optimization\Model\ResourceModel\ProductCache as ProductCacheResource;
use ArtM\Optimization\Model\ResourceModel\ProductCache\CollectionFactory as ProductCacheCollectionFactory;
use ArtM\Optimization\Model\ProductCacheFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

class ProductCacheRepository implements ProductCacheRepositoryInterface
{
    protected $productCacheResource;
    protected $productCacheFactory;
    protected $productCacheCollectionFactory;

    public function __construct(
        ProductCacheResource $productCacheResource,
        ProductCacheFactory $productCacheFactory,
        ProductCacheCollectionFactory $productCacheCollectionFactory
    ) {
        $this->productCacheResource = $productCacheResource;
        $this->productCacheFactory = $productCacheFactory;
        $this->productCacheCollectionFactory = $productCacheCollectionFactory;
    }

    public function save(ProductCacheInterface $productCache)
    {
        try {
            $this->productCacheResource->save($productCache);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the product cache: %1', $e->getMessage()));
        }
        return $productCache;
    }

    public function getById($id)
    {
        $productCache = $this->productCacheFactory->create();
        $this->productCacheResource->load($productCache, $id);
        if (!$productCache->getId()) {
            throw new NoSuchEntityException(__('The product cache with the "%1" ID doesn\'t exist.', $id));
        }
        return $productCache;
    }

    public function delete(ProductCacheInterface $productCache)
    {
        try {
            $this->productCacheResource->delete($productCache);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__('Could not delete the product cache: %1', $e->getMessage()));
        }
        return true;
    }

    public function deleteById($id)
    {
        $productCache = $this->getById($id);
        return $this->delete($productCache);
    }

    public function getByProductId($productId)
    {
        $productCache = $this->productCacheFactory->create();
        $this->productCacheResource->load($productCache, $productId, ProductCacheInterface::PRODUCT_ID);
        if (!$productCache->getId()) {
            throw new NoSuchEntityException(__('The product cache with the product ID "%1" doesn\'t exist.', $productId));
        }
        return $productCache;
    }

    public function getAllData()
    {
        $collection = $this->productCacheCollectionFactory->create();
        $data = [];
        foreach ($collection as $item) {
            $data[] = $item->getData();
        }
        return $data;
    }
}
