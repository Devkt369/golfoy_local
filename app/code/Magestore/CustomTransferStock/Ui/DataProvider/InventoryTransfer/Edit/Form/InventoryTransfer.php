<?php
declare(strict_types=1);

namespace Magestore\CustomTransferStock\Ui\DataProvider\InventoryTransfer\Edit\Form;

use Magento\Catalog\Helper\Image;
use Magestore\TransferStock\Model\InventoryTransfer\Option\Status;

/**
 * Class InventoryTransfer
 *
 * Fix bug Deprecated Functionality: strpos()
 */
class InventoryTransfer extends \Magestore\TransferStock\Ui\DataProvider\InventoryTransfer\Edit\Form\InventoryTransfer
{
    /**
     * Rewrite get data function
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getData()
    {
        if ($this->loadData) {
            return $this->loadData;
        }

        /** @var \Magestore\TransferStock\Model\InventoryTransfer $inventoryTransfer */
        $inventoryTransfer = $this->_coreRegistry->registry('current_inventory_transfer');
        if ($inventoryTransfer && $inventoryTransfer->getId()) {
            /** @var Image $imageCalalogHelper */
            $imageCalalogHelper = $this->_objectManager->get(Image::class);
            $inventoryTransferData = $inventoryTransfer->getData();

            // get source name
            $inventoryTransferData['source_warehouse_name']
                = $this->sourceRepository->get($inventoryTransfer->getSourceWarehouseCode())->getName();
            $inventoryTransferData['des_warehouse_name']
                = $this->sourceRepository->get($inventoryTransfer->getDesWarehouseCode())->getName();
            $inventoryTransferData['status_label']
                = ($inventoryTransferData['status'] == Status::STATUS_OPEN) ? __('Open') : __('Closed');

            $this->loadData[$inventoryTransfer->getId()]['transfer_summary']['general_information']
                = $inventoryTransferData;
            $products = $this->getProductsListData(
                $inventoryTransfer->getId(),
                $inventoryTransfer->getSourceWarehouseCode()
            );
            $productTypes = $this->type->getOptionArray();
            if (count($products)) {
                foreach ($products as $product) {
                    if ($product['image_url'] && strpos($product['image_url'], 'no_selection') !== false) {
                        $product['image_url'] = $imageCalalogHelper->getDefaultPlaceholderUrl('thumbnail');
                    }
                    $dataProduct = [
                        'id' => $product['product_id'],
                        'sku' => $product['product_sku'],
                        'name' => $product['product_name'],
                        'qty' => (float)$product['quantity'],
                        'qty_transferred' => (float)$product['qty_transferred'],
                        'qty_received' => (float)$product['qty_received'],
                        'image' => $product['image_url'],
                        'barcode' => $product['barcode'] ?? '',
                        'type' => isset($productTypes[$product['type_id']])
                            ? $productTypes[$product['type_id']]
                            : $product['type_id']
                    ];
                    $this->loadData[$inventoryTransfer->getId()]['transfer_summary']['product_list']
                    ['product_dynamic_grid']['links']['product_list'][] = $dataProduct;
                }
            }
        }

        foreach ($this->pool->getModifiersInstances() as $modifier) {
            $this->loadData = $modifier->modifyData($this->loadData);
        }
        return $this->loadData;
    }
}
