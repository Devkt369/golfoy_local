<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BarcodeSuccess\Model;

use Magento\Framework\App\ObjectManager;
use Magestore\BarcodeSuccess\Api\Data\BarcodeInterface;
use Magestore\BarcodeSuccess\Model\ResourceModel\Barcode as BarcodeResourceModel;

/**
 * Class Product
 *
 * Use for BarcodeSuccess
 */
class Product extends \Magento\Catalog\Model\Product
{
    /**
     * Migrate Barcode
     *
     * @param string $attributeCode
     * @param int|string|null $historyId
     */
    public function migrateBarcode($attributeCode, $historyId)
    {
        $objectManager = ObjectManager::getInstance();
        $barcodeResourceModel = $objectManager->get(BarcodeResourceModel::class);
        if ($attributeCode) {
            $barcodeAttribute = $this->getResource()->getAttribute($attributeCode);
            if ($barcodeAttribute) {
                $attributeValue = $barcodeAttribute->getFrontend()->getValue($this);
                if ($attributeValue) {
                    $data = [
                        BarcodeInterface::BARCODE => $attributeValue,
                        BarcodeInterface::PRODUCT_ID => $this->getId(),
                        BarcodeInterface::QTY => 1,
                        BarcodeInterface::PRODUCT_SKU => $this->getSku(),
                        BarcodeInterface::SUPPLIER_CODE => '',
                        BarcodeInterface::HISTORY_ID => $historyId
                    ];
                    $barcodeModel = $objectManager->create(BarcodeInterface::class);
                    $barcodeModel->setData($data);
                    $barcodeResourceModel->save($barcodeModel);
                }
            }
        }
    }
}
