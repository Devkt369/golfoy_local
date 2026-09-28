<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\SupplierSuccess\Service\Supplier;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Exception\LocalizedException;
use Magestore\SupplierSuccess\Model\ResourceModel\Supplier\PricingList;
use Magestore\SupplierSuccess\Model\ResourceModel\Supplier\PricingList\Collection;
use Magestore\SupplierSuccess\Model\Supplier;
use Magestore\SupplierSuccess\Service\AbstractService;
use Magestore\SupplierSuccess\Service\SupplierService;

/**
 * Service for pricing list
 */
class PricingListService extends AbstractService
{
    /**
     * Get supplier options
     *
     * @return array
     */
    public function getSupplierOptions()
    {
        /** @var \Magestore\SupplierSuccess\Model\ResourceModel\Supplier\Collection $supplierColletion */
        $supplierCollection = $this->supplierCollectionFactory->create();
        $suppliers = [];
        /** @var Supplier $supplier */
        foreach ($supplierCollection as $supplier) {
            $suppliers[$supplier->getId()] = $supplier->getSupplierName() . '('.$supplier->getSupplierCode().')';
        }
        return $suppliers;
    }

    /**
     * Import Pricing List To Supplier
     *
     * @param array $file
     * @throws Exception
     * @throws LocalizedException
     */
    public function importPricingListToSupplier($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new LocalizedException(__('Invalid file upload attempt.'));
        }
        $dataFile = $this->csvProcessor->getData($file['tmp_name']);
        if (count($dataFile)) {
            $importPricingListData = $this->getDataFromFile($dataFile, $importPricingListData);
            if (count($importPricingListData)) {
                $this->addPricingList($importPricingListData);
            }
        }
    }

    /**
     * Get data from file
     *
     * @param array $dataFile
     * @param array $importPricingListData
     * @return array
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getDataFromFile($dataFile, &$importPricingListData)
    {
        $importPricingListData = [];
        foreach ($dataFile as $col => $row) {
            if ($col == 0) {
                if (count($row)) {
                    foreach ($row as $index => $cell) {
                        $fields[$index] = (string)$cell;
                    }
                }
            } elseif ($col > 0) {
                if (count($row)) {
                    foreach ($row as $index => $cell) {
                        if (isset($fields[$index])) {
                            $importData[strtolower($fields[$index])] = $cell;
                        }
                    }
                    $this->getProductDataToImport($importPricingListData, $importData);
                }
            }
        }
        return $importPricingListData;
    }

    /**
     * Get Product Data To Import
     *
     * @param array $importPricingListData
     * @param array $importData
     * @throws LocalizedException
     */
    public function getProductDataToImport(&$importPricingListData, $importData)
    {
        /** @var ProductRepositoryInterface $productRepository */
        $productRepository = $this->objectManager->get(ProductRepositoryInterface::class);
        try {
            /** @var Product $product */
            $product = $productRepository->get($importData['product_sku']);
            if ($product->getId()) {
                /** @var SupplierService $supplierService */
                $supplierService = $this->objectManager->get(SupplierService::class);
                $supplier = $supplierService->getSupplierBySupplierCode($importData['supplier_code']);
                if ($supplier->getId()) {
                    $importData['supplier_id'] = $supplier->getId();
                    unset($importData['supplier_code']);
                    $importData['product_id'] = $product->getId();
                    $importData['product_name'] = $product->getName();
                    $importPricingListData[] = $importData;
                }
            }
        } catch (Exception $e) {
            throw new LocalizedException(__('Invalid pricing list data.'));
        }
    }

    /**
     * Add pricing list
     *
     * @param array $data
     */
    public function addPricingList($data)
    {
        /** @var PricingList $supplierPricingListResource */
        $supplierPricingListResource = $this->supplierPricingListResourceFactory->create();
        $supplierProductData = [];
        foreach ($data as $sp) {
            $sPData['supplier_id'] = $sp['supplier_id'];
            $sPData['product_id'] = $sp['product_id'];
            $sPData['product_sku'] = $sp['product_sku'];
            $sPData['product_name'] = $sp['product_name'];
            $supplierProductData[] = $sPData;
        }
        /** @var ProductService $supplierProductService */
        $supplierProductService = $this->objectManager->get(
            ProductService::class
        );
        $supplierProductService->assignProductToSupplier($supplierProductData);
        $supplierPricingListResource->addPricingList($data);
    }

    /**
     * Get product cost
     *
     * @param string|null $productSku
     * @param int $supplierId
     * @param string $time
     * @return array
     */
    public function getProductCost($productSku, $supplierId, $time = null)
    {
        /** @var Collection $pricingListCollection */
        $pricingListCollection = $this->supplierPricingListCollectionFactory->create();
        return $pricingListCollection->getProductCost($productSku, $supplierId, $time);
    }
}
