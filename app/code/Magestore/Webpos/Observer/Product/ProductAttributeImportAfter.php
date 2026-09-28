<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Observer\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magestore\Webpos\Api\Catalog\ProductRepositoryInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magestore\Webpos\Api\Data\Catalog\ProductInterface;
use Magestore\Webpos\Api\Log\ProductDeletedRepositoryInterface;
use Magestore\Webpos\Model\ResourceModel\Catalog\Product\Collection;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\Stdlib\DateTime;
use Magestore\Webpos\Model\Indexer\Product\Processor;
use Magestore\Webpos\Model\ResourceModel\Catalog\Product;

/**
 * Class ProductAttributeImportAfter
 *
 * Used for function visible on pos
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductAttributeImportAfter implements ObserverInterface
{
    /**
     * Data of visible on pos when import
     */
    const VISIBLE_ON_POS_IMPORT = 'yes';

    /**
     * @var ImportProduct
     */
    protected $import;

    /**
     * @var ProductDeletedRepositoryInterface
     */
    protected $productDeletedRepository;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * indexer processor
     *
     * @var Processor
     */
    protected $indexerProcessor;

    /**
     * indexer processor
     *
     * @var Product
     */
    protected $resourceProduct;

    /**
     * @var DateTime
     */
    protected $date;

    /**
     * @var DateTime\DateTime
     */
    protected $dateTime;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * ProductAttributeImportAfter constructor.
     *
     * @param ProductDeletedRepositoryInterface $productDeletedRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Processor $indexerProcessor
     * @param Product $resourceProduct
     * @param DateTime $date
     * @param DateTime\DateTime $dateTime
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        ProductDeletedRepositoryInterface $productDeletedRepository,
        ProductRepositoryInterface $productRepository,
        Processor $indexerProcessor,
        Product $resourceProduct,
        DateTime $date,
        DateTime\DateTime $dateTime,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->productDeletedRepository = $productDeletedRepository;
        $this->productRepository = $productRepository;
        $this->indexerProcessor = $indexerProcessor;
        $this->resourceProduct = $resourceProduct;
        $this->date = $date;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param EventObserver $observer
     * @throws LocalizedException
     */
    public function execute(EventObserver $observer)
    {
        $products = $observer->getEvent()->getBunch();
        $productIds = $this->deleteProductWebpos($products);
        $this->reindexProductSearchWebpos($productIds);
    }

    /**
     * Update data of product in table webpos_product_deleted
     *
     * @param array $products
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD)
     */
    private function deleteProductWebpos($products)
    {
        $productIds = [];
        try {
            foreach ($products as $product) {
                if (isset($product['webpos_visible'])) {
                    $visibleOnPos = $product['webpos_visible'] === self::VISIBLE_ON_POS_IMPORT ? 1 : 0;
                    /** @var ProductInterface $productItem */
                    $productItem = $this->productRepository->get($product[ImportProduct::COL_SKU]);
                    $productId = $productItem->getId();
                    $productIds[] = $productId;
                    if ($productItem->getStatus() == Status::STATUS_DISABLED ||
                        $visibleOnPos != Collection::VISIBLE_ON_WEBPOS
                    ) {
                        if ($productId) {
                            $this->productDeletedRepository->insertByProductId($productId);
                        }
                    } elseif ($productItem->getStatus() == Status::STATUS_ENABLED &&
                        $visibleOnPos == Collection::VISIBLE_ON_WEBPOS
                    ) {
                        $this->productDeletedRepository->deleteByProduct($productItem);
                    }
                }
            }
        } catch (LocalizedException $error) {
            $this->logger->error(__('Unable to delete product deleted in webpos'));
            $this->logger->error($error->getMessage());
        }
        return $productIds;
    }

    /**
     * Reindex webpos product search for list product changed
     *
     * @param array $productIds
     * @return void
     */
    private function reindexProductSearchWebpos($productIds)
    {
        if (!empty($productIds)) {
            $updatedAt = $this->date->formatDate($this->dateTime->gmtTimestamp());
            $productTable = $this->resourceProduct->getTable('catalog_product_entity');
            $this->resourceProduct->updateUpdatedTimeOfProducts($productTable, $updatedAt, $productIds);

            // reindex then update by scheduled mode
            if ($this->indexerProcessor->isIndexerScheduled()) {
                $this->indexerProcessor->markIndexerAsInvalid();
            } else {
                // reindex when update on save mode
                $this->indexerProcessor->reindexList($productIds);
            }
        }
    }
}
