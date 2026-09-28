<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;
use Magestore\InventoryMovementApi\Api\GetProductInfoForMovementInterface;

/**
 * Service for get product info for movement (product name and id)
 *
 * @api
 */
class GetProductInfoForMovement implements GetProductInfoForMovementInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * GetProductInfoForMovement constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Execute service
     *
     * @param array $movements
     * @return array
     */
    public function execute(array $movements): array
    {
        $productNames = [];
        $productIds = [];

        $productCollection = $this->productRepository->getList(
            $this->searchCriteriaBuilder->addFilter(
                ProductInterface::SKU,
                array_unique(array_column($movements, InventoryMovementInterface::PRODUCT_SKU)),
                'in'
            )->create()
        )->getItems();

        foreach ($productCollection as $product) {
            $productNames[$product->getSku()] = $product->getName();
            $productIds[$product->getSku()] = $product->getId();
        }

        return [
            'product_names' => $productNames,
            'product_ids' => $productIds
        ];
    }
}
