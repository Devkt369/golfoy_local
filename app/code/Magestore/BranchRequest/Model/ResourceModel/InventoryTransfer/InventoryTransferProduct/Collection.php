<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;

/**
 * Inventory transfer product collection
 *
 * @SuppressWarnings(PHPMD)
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_map = [
        'available_qty_to_delivery' => 'qty_requested - qty_delivered',
        'available_qty_to_receive' => 'qty_delivered - qty_received',
        'total_qty' => 'IFNULL(current_inventory_source_item.quantity, 0)',
        'barcode' => 'GROUP_CONCAT(DISTINCT barcode.barcode)'
    ];

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            \Magestore\BranchRequest\Model\InventoryTransfer\InventoryTransferProduct::class,
            \Magestore\BranchRequest\Model\ResourceModel\InventoryTransfer\InventoryTransferProduct::class
        );
    }

    /**
     * Add Transfer Id To Filter
     *
     * @param int $transferId
     */
    public function addTransferIdToFilter($transferId)
    {
        $this->addFieldToFilter('inventorytransfer_id', $transferId);
    }

    /**
     * Get transferred product
     *
     * @param int $transferId
     * @return Collection
     */
    public function getTransferedProducts($transferId)
    {
        /** copy từ transfer nhưng nên đi từ product collecttion - có thời gian sẽ sửa */
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        /** @var \Magento\Framework\Module\Manager $moduleManager */
        $moduleManager = $om->get(\Magento\Framework\Module\Manager::class);
        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $transferModel */
        $transferModel = $om->create(\Magestore\BranchRequest\Model\InventoryTransfer::class)->load($transferId);
        $this->addTransferIdToFilter($transferId);
        // get image
        $storeManager = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Store\Model\StoreManagerInterface::class);
        $path = $storeManager->getStore()->getBaseUrl(
            \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
        );
        $path .= 'catalog/product';
        $edition = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\App\ProductMetadataInterface::class)
            ->getEdition();
        $rowId = strtolower($edition) == 'enterprise' ? 'row_id' : 'entity_id';
        /** @var \Magento\Eav\Model\ResourceModel\Entity\Attribute $eavAttribute */
        $eavAttribute = \Magento\Framework\App\ObjectManager::getInstance()
            ->create(\Magento\Eav\Model\ResourceModel\Entity\Attribute::class);
        $productImagesAttributeId = $eavAttribute->getIdByCode(
            \Magento\Catalog\Model\Product::ENTITY,
            'thumbnail'
        );
        $this->getSelect()->joinLeft(
            ['catalog_product_entity_varchar_img' => $this->getTable('catalog_product_entity_varchar')],
            "main_table.product_id = catalog_product_entity_varchar_img.$rowId && 
                catalog_product_entity_varchar_img.attribute_id = $productImagesAttributeId && 
                catalog_product_entity_varchar_img.store_id = 0",
            ['']
        )->columns(
            [
                'image' => 'catalog_product_entity_varchar_img.value',
                'image_url' => 'CONCAT("' . $path . '", catalog_product_entity_varchar_img.value)'
            ]
        );

        $resource = $this->getResource();
        if ($sendingSourceCode = $transferModel->getData(InventoryTransferInterface::FROM)) {
            $sourceItemTable = $resource->getTable('inventory_source_item');
            $this->getSelect()->joinLeft(
                ['current_inventory_source_item' => $sourceItemTable],
                "main_table.product_sku = current_inventory_source_item.sku AND
                current_inventory_source_item.source_code = '$sendingSourceCode'",
                ['quantity', 'racket']
            );
            $this->getSelect()->columns(
                [
                    'total_qty' => new \Zend_Db_Expr($this->_map['total_qty'])
                ]
            );
        }

        $barcodeTable = $resource->getTable('os_barcode');
        if ($moduleManager->isEnabled('Magestore_BarcodeSuccess')) {
            $this->getSelect()->joinLeft(
                ['barcode' => $barcodeTable],
                "main_table.product_sku = barcode.product_sku",
                ['barcode']
            );
            $this->getSelect()->columns(
                [
                    'barcode' => new \Zend_Db_Expr($this->_map['barcode']),
                    'barcode_original_data' => new \Zend_Db_Expr($this->_map['barcode'])
                ]
            );
        }

        $productTable = $resource->getTable('catalog_product_entity');
        $this->getSelect()->joinLeft(
            ['product' => $productTable],
            "main_table.product_id = product.entity_id",
            ['type_id']
        );

        $this->getSelect()->group('main_table.product_sku');
        $this->getSelect()->columns(
            [
                'available_qty_to_delivery' => new \Zend_Db_Expr($this->_map['available_qty_to_delivery']),
                'available_qty_to_receive' => new \Zend_Db_Expr($this->_map['available_qty_to_receive']),
            ]
        );
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function addFieldToFilter($field, $condition = null)
    {
        if (isset($this->_map[$field]) && $this->_map[$field]) {
            $field = new \Zend_Db_Expr($this->_map[$field]);
        }
        return parent::addFieldToFilter($field, $condition);
    }
}
