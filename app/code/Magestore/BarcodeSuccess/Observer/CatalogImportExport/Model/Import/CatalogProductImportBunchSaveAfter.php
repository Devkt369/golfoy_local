<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\BarcodeSuccess\Observer\CatalogImportExport\Model\Import;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\CatalogImportExport\Model\Import\Product as ImportProductModel;
use Magestore\BarcodeSuccess\Api\Data\BarcodeInterface;
use Magestore\BarcodeSuccess\Helper\BarcodeProvider;
use Magestore\BarcodeSuccess\Model\History;
use Magestore\BarcodeSuccess\Helper\Data as Helper;
use Magestore\BarcodeSuccess\Helper\Attribute as AttributeHelper;
use Psr\Log\LoggerInterface;

/**
 * Import bunch product save after observer
 */
class CatalogProductImportBunchSaveAfter implements ObserverInterface
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var AttributeHelper
     */
    protected $attributeHelper;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var BarcodeProvider
     */
    protected $barcodeProvider;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param Helper $helper
     * @param AttributeHelper $attributeHelper
     * @param ResourceConnection $resourceConnection
     * @param BarcodeProvider $barcodeProvider
     * @param LoggerInterface $logger
     */
    public function __construct(
        Helper $helper,
        AttributeHelper $attributeHelper,
        ResourceConnection $resourceConnection,
        BarcodeProvider $barcodeProvider,
        LoggerInterface $logger
    ) {
        $this->helper = $helper;
        $this->attributeHelper = $attributeHelper;
        $this->resourceConnection = $resourceConnection;
        $this->barcodeProvider = $barcodeProvider;
        $this->logger = $logger;
    }

    /**
     * Execute observer
     *
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        try {
            $attributeCode = $this->helper->getAttributeCode();
            $bunch = $observer->getData('bunch');
            $totalCount = count($bunch);
            if (!$attributeCode || !$totalCount) {
                return;
            }
            $skus = [];
            foreach ($bunch as $rowData) {
                $sku = $rowData[ImportProductModel::COL_SKU];
                $skus[$sku] = $sku;
            }

            $this->deleteOldBarcodes($skus);

            $products = $this->getProducts($skus);
            $historyId = $this->attributeHelper->saveHistory($totalCount, History::GENERATED, __('Import products'));

            $data = [];
            if ($attributeCode === 'sku') {
                foreach ($products as $product) {
                    $data[] = [
                        BarcodeInterface::BARCODE => $product['sku'],
                        BarcodeInterface::PRODUCT_ID => $product['entity_id'],
                        BarcodeInterface::QTY => 1,
                        BarcodeInterface::PRODUCT_SKU => $product['sku'],
                        BarcodeInterface::SUPPLIER_CODE => '',
                        BarcodeInterface::HISTORY_ID => $historyId
                    ];
                }
            } else {
                $values = $this->getProductsAttributeValue($products);
                foreach ($products as $product) {
                    if (isset($values[$product['entity_id']])) {
                        $data[] = [
                            BarcodeInterface::BARCODE => $values[$product['entity_id']],
                            BarcodeInterface::PRODUCT_ID => $product['entity_id'],
                            BarcodeInterface::QTY => 1,
                            BarcodeInterface::PRODUCT_SKU => $product['sku'],
                            BarcodeInterface::SUPPLIER_CODE => '',
                            BarcodeInterface::HISTORY_ID => $historyId
                        ];
                    }
                }
            }

            $connection = $this->resourceConnection->getConnection();
            $barcodeTableName = $this->resourceConnection->getTableName('os_barcode');
            $connection->insertMultiple($barcodeTableName, $data);
        } catch (\Exception $exception) {
            $this->logger->info($exception->getMessage());
        }
    }

    /**
     * Get Products
     *
     * @param array $skus
     * @return array
     */
    public function getProducts(array $skus = [])
    {
        if (empty($skus)) {
            return [];
        }
        $connection = $this->resourceConnection->getConnection();
        $productTableName = $this->resourceConnection->getTableName('catalog_product_entity');
        $select = $connection->select()
            ->from($productTableName, ['entity_id', 'sku'])
            ->where('sku IN (?)', $skus);
        return $connection->fetchAll($select);
    }

    /**
     * Get Products Attribute Value
     *
     * @param array $products
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductsAttributeValue(array $products)
    {
        if (empty($products)) {
            return [];
        }
        $productIds = [];

        foreach ($products as $product) {
            $productIds[] = $product["entity_id"];
        }
        $barcodeAttribute = $this->barcodeProvider->getBarcodeAttribute();
        $res = $this->barcodeProvider->getProductAttribute($productIds);

        $values = [];

        foreach ($res as $productId => $productData) {
            if ($barcodeAttribute) {
                $values[$productId] = $productData[$barcodeAttribute->getAttributeId()];
            }
        }
        return $values;
    }

    /**
     * Delete Old Barcodes
     *
     * @param array $skus
     */
    public function deleteOldBarcodes(array $skus = [])
    {
        if (empty($skus)) {
            return;
        }
        $connection = $this->resourceConnection->getConnection();
        $barcodeTable = $this->resourceConnection->getTableName('os_barcode');
        $connection->delete(
            $barcodeTable,
            [BarcodeInterface::PRODUCT_SKU . ' IN (?)' => $skus]
        );
    }
}
