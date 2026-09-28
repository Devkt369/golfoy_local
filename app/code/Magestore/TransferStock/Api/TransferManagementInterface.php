<?php

namespace Magestore\TransferStock\Api;

interface TransferManagementInterface
{
    /**
     * Start to send stock by transfer id
     *
     * @param int $transferId
     * @return array
     */
    public function startToSendStock($transferId);

    /**
     * Get product from transfer id
     *
     * @param int $transferId
     * @return \Magestore\TransferStock\Api\Data\InventoryTransfer\InventoryTransferProductInterface[]
     */
    public function getProductFromInventoryTransfer($transferId);

    /**
     * Delete products in transfer inventory
     *
     * @param int $transferId
     * @return bool
     */
    public function deleteProductsInInventoryTransfer($transferId);

    /**
     * Set products for transfer inventory
     *
     * @param int $transferId
     * @param array $items
     * @return bool
     */
    public function setProductsForInventoryTransfer($transferId, $items);

    /**
     * Get select barcode json for product list
     *
     * @param array $productIds
     * @return mixed
     */
    public function getSelectBarcodeProductListJson($productIds = []);

    /**
     * Get product json by barcode function
     *
     * @param string $barcode
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductJsonByBarcode(string $barcode);

    /**
     * Get select barcode receiving product list json function
     *
     * @param int $transferId
     * @return mixed
     */
    public function getSelectBarcodeReceivingProductListJson($transferId);

    /**
     * Get product json by transfer id and barcode function
     *
     * @param int $transferId
     * @param string $barcode
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getProductReceivingJsonByBarcode(int $transferId, string $barcode);

    /**
     * Add products to transfer inventory
     *
     * @param int $transferId
     * @param array $items
     * @return bool
     */
    public function addProductsToInventoryTransfer($transferId, $items);

    /**
     * Receive products by transfer id and items
     *
     * @param int $transferId
     * @param array $items
     * @return bool
     */
    public function receiveProducts($transferId, $items);

    /**
     * Receive all products by transfer id
     *
     * @param int $transferId
     * @return bool
     */
    public function receiveAllProducts($transferId);
}
