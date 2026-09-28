<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;

class ProductStorage
{
    /**
     * @var ProductInterface[]
     */
    private array $productsBySku = [];

    /**
     * @var ProductInterface[]
     */
    private array $productsById = [];

    /**
     * @var string[]
     */
    private array $invalidSkus = [];

    /**
     * @var int[]
     */
    private array $invalidIds = [];

    /**
     * @var ProductRepositoryInterface
     */
    private ProductRepositoryInterface $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @param string[] $skus
     * @return ProductInterface[]
     */
    public function getProducts(array $skus): array
    {
        $skusToLoad = array_diff(
            $skus,
            array_keys($this->productsBySku),
            $this->invalidSkus
        );

        if (!empty($skusToLoad)) {
            $products = $this->loadProducts('sku', $skusToLoad);
            $loadedData = $this->storageData($products);

            $this->invalidSkus = array_unique(array_merge(
                $this->invalidSkus,
                array_diff($skusToLoad, $loadedData['loadedSkus'])
            ));
        }

        return array_intersect_key($this->productsBySku, array_flip($skus));
    }

    /**
     * @param int[] $productIds
     * @return ProductInterface[]
     */
    public function getProductsByIds(array $productIds): array
    {
        $idsToLoad = array_diff(
            $productIds,
            array_keys($this->productsById),
            $this->invalidIds
        );

        if (!empty($idsToLoad)) {
            $products = $this->loadProducts('entity_id', $idsToLoad);
            $loadedData = $this->storageData($products);

            $this->invalidIds = array_unique(array_merge(
                $this->invalidIds,
                array_diff($idsToLoad, $loadedData['loadedIds'])
            ));
        }

        return array_intersect_key($this->productsById, array_flip($productIds));
    }

    public function getProductById(int $productId): ProductInterface
    {
        $products = $this->getProductsByIds([$productId]);
        if (!isset($products[$productId])) {
            throw new NoSuchEntityException(
                __("The product that was requested doesn't exist. Verify the product and try again.")
            );
        }

        return $products[$productId];
    }

    public function getProduct(string $sku): ProductInterface
    {
        $products = $this->getProducts([$sku]);
        if (!isset($products[$sku])) {
            throw new NoSuchEntityException(
                __("The product that was requested doesn't exist. Verify the product and try again.")
            );
        }

        return $products[$sku];
    }

    /**
     * @param string $fieldName
     * @param string[]|int[] $fieldData
     * @return ProductInterface[]
     */
    private function loadProducts(string $fieldName, array $fieldData): array
    {
        // use getList because we need all attributes for condition validation
        return $this->productRepository->getList(
            $this->searchCriteriaBuilder
                ->addFilter($fieldName, $fieldData, 'IN')
                ->create()
        )->getItems();
    }

    /**
     * @param ProductInterface[] $products
     * @return array{ loadedSkus: string[], loadedIds: int[] }
     */
    private function storageData(array $products): array
    {
        $loadedSkus = [];
        $loadedIds = [];

        foreach ($products as $product) {
            $sku = $product->getSku();
            $id = $product->getId();
            $this->productsBySku[$sku] = $product;
            $this->productsById[$id] = $product;
            $loadedSkus[] = $sku;
            $loadedIds[] = $id;
        }

        return ['loadedSkus' => $loadedSkus, 'loadedIds' => $loadedIds];
    }

    public function _resetState(): void
    {
        $this->productsBySku = [];
        $this->productsById = [];
        $this->invalidSkus = [];
        $this->invalidIds = [];
    }
}
