<?php
/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magestore\ReportSuccess\Observer\PurchaseOrder\Item;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Action as ProductAction;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Model\Stock\Item;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\InventoryApi\Api\GetSourceItemsBySkuInterface;
use Magestore\PurchaseOrderSuccess\Model\PurchaseOrder;
use Magestore\PurchaseOrderSuccess\Model\PurchaseOrder\Item\Transferred;
use Magestore\ReportSuccess\Api\ReportManagementInterface;

/**
 * Class TransferredSaveAfter
 *
 * Used to observer event save after transfer
 */
class TransferredSaveAfter implements ObserverInterface
{
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Item
     */
    protected $stockItem;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var ReportManagementInterface
     */
    protected $reportManagement;

    /**
     * TransferredSaveAfter constructor.
     *
     * @param ObjectManagerInterface $objectManager
     * @param ProductFactory $productFactory
     * @param Item $stockItem
     * @param ScopeConfigInterface $scopeConfig
     * @param ReportManagementInterface $reportManagement
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ProductFactory $productFactory,
        Item $stockItem,
        ScopeConfigInterface $scopeConfig,
        ReportManagementInterface $reportManagement
    ) {
        $this->objectManager = $objectManager;
        $this->_productFactory = $productFactory;
        $this->stockItem = $stockItem;
        $this->scopeConfig = $scopeConfig;
        $this->reportManagement = $reportManagement;
    }

    /**
     * Execute the observer
     *
     * @param EventObserver $observer
     * @return EventObserver
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(EventObserver $observer)
    {
        if (get_class($observer->getData('object'))
            // phpcs:ignore Magento2.PHP.LiteralNamespaces.LiteralClassUsage
            == 'Magestore\PurchaseOrderSuccess\Model\PurchaseOrder\Item\Transferred') {
            /** @var Transferred $transferredItem */
            $transferredItem = $observer->getData('object');
            /** @var \Magestore\PurchaseOrderSuccess\Model\PurchaseOrder\Item $purchaseOrderItem */
            $purchaseOrderItem = $transferredItem->getPurchaseOrderItem();
            /** @var PurchaseOrder $purchaseOrder */
            $purchaseOrder = $purchaseOrderItem->getPurchaseOrder();
            /** @var Product $product */
            $product = $this->_productFactory->create()
                ->loadByAttribute('sku', $purchaseOrderItem->getProductSku());
            if ($product && $product->getId()) {
                if ((float)$purchaseOrder->getSubtotal() > 0
                    && $this->scopeConfig->getValue('reportsuccess/include_costing_method/include_shipping')) {
                    $shippingFeePerItem = $purchaseOrderItem->getCost() / (float)$purchaseOrder->getSubtotal()
                        * $purchaseOrder->getShippingCost();
                } else {
                    $shippingFeePerItem = 0;
                }
                $mac = $product->getMac();
                $cost = $product->getCost();
                if (!$mac && $cost) {
                    $mac = $cost;
                }

                $tax = $discount = 0;
                if ($this->scopeConfig->getValue('reportsuccess/include_costing_method/include_discount')) {
                    $discount = $purchaseOrderItem->getDiscount();
                }
                if ($this->scopeConfig->getValue('reportsuccess/include_costing_method/include_tax')) {
                    $tax = $purchaseOrderItem->getTax();
                }

                $landedCost = $purchaseOrderItem->getCost() * (100 + $tax - $discount) / 100 + $shippingFeePerItem;
                if ($purchaseOrder->getCurrencyRate()) {
                    $landedCost /= $purchaseOrder->getCurrencyRate();
                }
                if (!$mac) {
                    $mac = $landedCost;
                } else {
                    $stockQty = 0;
                    if ($this->reportManagement->isMSIEnable()) {
                        /** @var GetSourceItemsBySkuInterface $getSourceItemsBySku */
                        $getSourceItemsBySku = $this->objectManager
                            ->get(GetSourceItemsBySkuInterface::class);
                        $sourceItems = $getSourceItemsBySku->execute($product->getSku());
                        foreach ($sourceItems as $sourceItem) {
                            $stockQty += $sourceItem->getQuantity();
                        }
                    } else {
                        $stockItem = $this->stockItem->load($product->getId(), 'product_id');
                        $stockQty = $stockItem->getQty();
                    }
                    $currentStockValue = $stockQty * $mac;
                    $newStockValue = $landedCost * $transferredItem->getQtyTransferred();
                    $newQty = $stockQty + $transferredItem->getQtyTransferred();
                    $mac = ($currentStockValue + $newStockValue) / $newQty;
                }
                $mac = round($mac, 2);
                /* @var ProductAction $action */
                $action = $this->objectManager->get(ProductAction::class);
                $action->updateAttributes(
                    [$product->getId()],
                    ['mac' => $mac],
                    $product->getStoreId()
                );
            }
        }
        return $observer;
    }
}
