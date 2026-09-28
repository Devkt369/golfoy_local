<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Plugin\Inventory\Model\ResourceModel\SourceItem;

use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Inventory\Model\ResourceModel\SourceItem\DeleteMultiple as CoreDeleteMultiple;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magestore\Core\Api\GetStockIdsBySourceCodesInterface;
use Magestore\Webpos\Api\Log\ProductDeletedRepositoryInterface;
use Magestore\Webpos\Model\ResourceModel\Inventory\Stock\Item;
use Psr\Log\LoggerInterface;

/**
 * Add product deleted data after save multiple source items
 */
class AddProductDeletedDataAfterDeleteMultiple
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var Item
     */
    protected $stockItemResource;
    /**
     * @var ProductDeletedRepositoryInterface
     */
    protected $productDeletedRepository;
    /**
     * @var Product
     */
    protected $productResource;
    /**
     * @var GetStockIdsBySourceCodesInterface
     */
    protected $getStockIdsBySourceCodes;

    /**
     * @param LoggerInterface $logger
     * @param Item $stockItemResource
     * @param ProductDeletedRepositoryInterface $productDeletedRepository
     * @param Product $productResource
     * @param GetStockIdsBySourceCodesInterface $getStockIdsBySourceCodes
     */
    public function __construct(
        LoggerInterface $logger,
        Item $stockItemResource,
        ProductDeletedRepositoryInterface $productDeletedRepository,
        Product $productResource,
        GetStockIdsBySourceCodesInterface $getStockIdsBySourceCodes
    ) {
        $this->logger = $logger;
        $this->stockItemResource = $stockItemResource;
        $this->productDeletedRepository = $productDeletedRepository;
        $this->productResource = $productResource;
        $this->getStockIdsBySourceCodes = $getStockIdsBySourceCodes;
    }

    /**
     * Add product deleted data after save multiple source items
     *
     * @param CoreDeleteMultiple $subject
     * @param mixed $result
     * @param SourceItemInterface[] $sourceItems
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        CoreDeleteMultiple $subject,
        $result,
        array $sourceItems
    ) {
        if (!count($sourceItems)) {
            return;
        }
        try {
            $sourcesBySkus = [];
            foreach ($sourceItems as $sourceItem) {
                $sourcesBySkus[$sourceItem->getSku()][] = $sourceItem->getSourceCode();
            }
            $skus = array_keys($sourcesBySkus);
            $this->stockItemResource->updateUpdatedTimeBySku($skus);
            $productIds = $this->productResource->getProductsIdsBySkus($skus);

            foreach ($sourcesBySkus as $sku => $sourceCodes) {
                $stockIds = $this->getStockIdsBySourceCodes->execute($sourceCodes);
                if (isset($productIds[$sku]) && count($stockIds)) {
                    $this->productDeletedRepository->insertByProductIdAndStock($productIds[$sku], $stockIds);
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }
}
