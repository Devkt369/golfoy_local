<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BarcodeSuccess\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magestore\BarcodeSuccess\Api\Data\BarcodeInterface;
use Magestore\BarcodeSuccess\Model\History;

/**
 * Class Attribute
 *
 * Import barcode from product's attribute
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class Attribute extends AbstractHelper
{
    const PAGE_SIZE = 500;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $date;

    /**
     * @var \Magestore\BarcodeSuccess\Api\Data\HistoryInterface
     */
    protected $historyInterface;

    /**
     * @var \Magestore\BarcodeSuccess\Model\ResourceModel\History
     */
    protected $historyResource;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $session;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @var BarcodeProvider
     */
    private $barcodeProvider;

    /**
     * Attribute constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magestore\BarcodeSuccess\Api\Data\HistoryInterface $historyInterface
     * @param \Magestore\BarcodeSuccess\Model\ResourceModel\History $historyResource
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Backend\Model\Auth\Session $session
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date
     * @param \Magento\Framework\App\ResourceConnection $resourceConnection
     * @param \Magestore\BarcodeSuccess\Helper\BarcodeProvider $barcodeProvider
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magestore\BarcodeSuccess\Api\Data\HistoryInterface $historyInterface,
        \Magestore\BarcodeSuccess\Model\ResourceModel\History $historyResource,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\Auth\Session $session,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $date,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magestore\BarcodeSuccess\Helper\BarcodeProvider $barcodeProvider
    ) {
        $this->historyInterface = $historyInterface;
        $this->historyResource = $historyResource;
        $this->session = $session;
        $this->messageManager = $messageManager;
        $this->date = $date;
        $this->resourceConnection = $resourceConnection;
        $this->barcodeProvider = $barcodeProvider;
        parent::__construct($context);
    }

    /**
     * Save History
     *
     * @param int $totalQty
     * @param string $type
     * @param string $reason
     * @return int|string|null
     */
    public function saveHistory($totalQty, $type, $reason = '')
    {
        $historyId = '';
        $history = $this->historyInterface;
        $historyResource = $this->historyResource;
        $adminSession = $this->session;
        try {
            $admin = $adminSession->getUser();
            $adminId = ($admin) ? $admin->getId() : 0;
            $history->setData('type', $type);
            $history->setData('reason', $reason);
            $history->setData('created_by', $adminId);
            $history->setData('total_qty', $totalQty);
            $historyResource->save($history);
            $historyId = $history->getId();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $historyId;
    }

    /**
     * Import To Barcode
     *
     * @param string $attributeCode
     */
    public function importToBarcode($attributeCode)
    {
        $connection = $this->resourceConnection->getConnection();
        $productTableName = $this->resourceConnection->getTableName('catalog_product_entity');
        $select = $connection->select()->from($productTableName, ['entity_id', 'sku']);
        $products = $connection->fetchAll($select);

        $historyId = $this->saveHistory(count($products), History::GENERATED, __('Import from attribute'));

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
            $productIds = [];

            foreach ($products as $product) {
                $productIds[] = $product["entity_id"];
            }

            $barcodeAttribute = $this->barcodeProvider->getBarcodeAttribute($attributeCode);
            $res = $this->barcodeProvider->getProductAttribute($productIds, $attributeCode);

            $values = [];

            foreach ($res as $productId => $productData) {
                if ($barcodeAttribute) {
                    $values[$productId]
                        = $productData[$barcodeAttribute->getAttributeId()];
                }
            }

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

        $barcodeTableName = $this->resourceConnection->getTableName('os_barcode');
        $connection->truncateTable($barcodeTableName);
        $batches = array_chunk($data, self::PAGE_SIZE);
        foreach ($batches as $batch) {
            $connection->insertMultiple($barcodeTableName, $batch);
        }
        $connection->update(
            $productTableName,
            ['updated_at' => $this->date->date()->format('Y-m-d H:i:s')]
        );
    }
}
