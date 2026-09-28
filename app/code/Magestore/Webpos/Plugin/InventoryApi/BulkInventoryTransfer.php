<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Plugin\InventoryApi;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Registry;
use Magento\InventoryCatalogApi\Api\BulkInventoryTransferInterface;
use Magestore\Webpos\Helper\Data;
use Magestore\Webpos\Model\Indexer\Product as PosIndexer;
use Psr\Log\LoggerInterface;

/**
 * Class BulkInventoryTransfer
 *
 * Reindex product search when bulk inventory transfer
 */
class BulkInventoryTransfer
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var PosIndexer
     */
    protected $posIndexer;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var ProductResource
     */
    protected $productResource;

    /**
     * BulkInventoryTransfer constructor.
     *
     * @param Data $helper
     * @param LoggerInterface $logger
     * @param PosIndexer $posIndexer
     * @param ProductResource $productResource
     */
    public function __construct(
        Data $helper,
        LoggerInterface $logger,
        PosIndexer $posIndexer,
        ProductResource $productResource
    ) {
        $this->helper = $helper;
        $this->logger = $logger;
        $this->posIndexer = $posIndexer;
        $this->productResource = $productResource;
    }

    /**
     * Reindex by row
     *
     * @param BulkInventoryTransferInterface $subject
     * @param bool $result
     * @param array $skus
     * @param string $originSource
     * @param string $destinationSource
     * @param bool $unassignFromOrigin
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(
        BulkInventoryTransferInterface $subject,
        bool $result,
        array $skus,
        string $originSource,
        string $destinationSource,
        bool $unassignFromOrigin
    ) {
        if (!$this->helper->isEnableElasticSearch()) {
            return $result;
        }
        $productsNeedToReindex = $this->getProductIdsFromSkus($skus);
        try {
            $this->posIndexer->executeList($productsNeedToReindex);
        } catch (\Exception $e) {
            $this->logger->info($e->getTraceAsString());
        }
        return $result;
    }

    /**
     * Get product ids to reindex
     *
     * @param array $skus
     * @return array
     */
    public function getProductIdsFromSkus(array $skus)
    {
        if (!empty($skus)) {
            $productTable = $this->productResource->getTable('catalog_product_entity');
            $connection = $this->productResource->getConnection();
            $statement = $connection->select()->from(
                $productTable,
                ['entity_id']
            )->where(
                ProductInterface::SKU . ' IN (?)',
                $skus
            );
            $productIds = $connection->fetchCol($statement);
            return array_unique($productIds);
        } else {
            return [];
        }
    }
}
