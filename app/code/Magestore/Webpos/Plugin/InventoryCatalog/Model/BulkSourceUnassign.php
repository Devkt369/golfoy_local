<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Plugin\InventoryCatalog\Model;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\InventoryCatalog\Model\BulkSourceUnassign as CoreBulkSourceUnassign;
use Magestore\Core\Api\GetStockIdsBySourceCodesInterface;
use Magestore\Webpos\Api\Log\ProductDeletedRepositoryInterface;
use Magestore\Webpos\Model\ResourceModel\Inventory\Stock\Item;

/**
 * After unassign products from sources
 */
class BulkSourceUnassign
{
    /**
     * @var Item
     */
    private $stockItemResource;
    /**
     * @var ProductDeletedRepositoryInterface
     */
    private $productDeletedRepository;
    /**
     * @var Product
     */
    private $productResource;
    /**
     * @var GetStockIdsBySourceCodesInterface
     */
    private $getStockIdsBySourceCodes;

    /**
     * BulkSourceUnassign constructor.
     * @param Item $stockItemResource
     * @param ProductDeletedRepositoryInterface $productDeletedRepository
     * @param Product $productResource
     * @param GetStockIdsBySourceCodesInterface $getStockIdsBySourceCodes
     */
    public function __construct(
        Item $stockItemResource,
        ProductDeletedRepositoryInterface $productDeletedRepository,
        Product $productResource,
        GetStockIdsBySourceCodesInterface $getStockIdsBySourceCodes
    ) {
        $this->stockItemResource = $stockItemResource;
        $this->productDeletedRepository = $productDeletedRepository;
        $this->productResource = $productResource;
        $this->getStockIdsBySourceCodes = $getStockIdsBySourceCodes;
    }

    /**
     * After execute
     *
     * @param CoreBulkSourceUnassign $subject
     * @param int $result
     * @param array $skus
     * @param array $sourceCodes
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        CoreBulkSourceUnassign $subject,
        int $result,
        array $skus,
        array $sourceCodes
    ) {
        if (count($skus)) {
            $this->stockItemResource->updateUpdatedTimeBySku($skus);
            $productIds = $this->productResource->getProductsIdsBySkus($skus);
            $stockIds = $this->getStockIdsBySourceCodes->execute($sourceCodes);

            if (count($stockIds)) {
                foreach (array_values($productIds) as $productId) {
                    $this->productDeletedRepository->insertByProductIdAndStock($productId, $stockIds);
                }
            }
        }
        return $result;
    }
}
