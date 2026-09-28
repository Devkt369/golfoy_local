<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Helper;

/**
 * Attributes management
 */
class AttributesInventory extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $helperData;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        /** @delegation off */
        \Magento\Framework\App\Helper\Context $context
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context);
    }
    /**
     * @param $model
     * @return int
     */
    public function getStockId($model)
    {
        $stockId = \Magento\CatalogInventory\Model\Stock::DEFAULT_STOCK_ID;
        if ($model->inventoryStocks) {
            $stockId = $model->stockId;
        }
        return $stockId;
    }
    /**
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param \Magento\Catalog\Model\Product $product
     * @param $stockId
     * @return array
     */
    public function getStock($model, $product, $stockId)
    {
        if (isset($model->inventoryStocks[$stockId][$product->getId()])) {
            $stocks = $model->inventoryStocks[$stockId][$product->getId()];
        } else {
            $stocks = ['is_salable' => 0, 'quantity' => 0];
        }
        return $stocks;
    }
    public function getSource($model, $product, $sourceCode)
    {
        if (isset($model->inventorySources[$product->getId()][$sourceCode])) {
            $stocks = $model->inventorySources[$product->getId()][$sourceCode];
        } else {
            $stocks = ['is_salable' => 0, 'quantity' => 0];
        }
        return $stocks;
    }
    /**
     * {g_availability} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return string the availability of the product enclosed between tags
     */
    public function availability($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $inStock = !isset($options['in_stock']) ? 'in stock' : $options['in_stock'];
        $outOfStock = !isset($options['out_of_stock']) ? "out of stock" : $options['out_of_stock'];
        $availableForOrder = !isset($options['pre_order']) ? "preorder" : $options['pre_order'];
        $stockId = $this->getStockId($model);
        $stockId = !isset($options['stock_id']) ? $stockId : $options['stock_id'];
        $sourceCode = !isset($options['source_code']) ? false : $options['source_code'];
        if ($item->getManageStock() && !$item->getUseConfigManageStock() && !$model->manageStock || $item->getUseConfigManageStock() && $model->manageStock || $item->getManageStock() && !$item->getUseConfigManageStock()) {
            if (!$this->helperData->isMsiEnabled()) {
                if ($item->getIsInStock() > 0) {
                    if ($item->getTypeId() == 'configurable') {
                        if (isset($model->configurableQty[$item->getId()])) {
                            $qty = $model->configurableQty[$item->getId()];
                        } else {
                            $qty = $item->getQty();
                        }
                    } else {
                        $qty = $item->getQty();
                    }
                    if ($qty > 0) {
                        $value = $inStock;
                    } else {
                        if ($item->getBackorders() || $item->getUseConfigBackorders() && $model->backorders) {
                            $value = $availableForOrder;
                        } else {
                            $value = $outOfStock;
                        }
                    }
                } else {
                    $value = $outOfStock;
                }
            } else {
                if ($sourceCode) {
                    $inventory = $this->getSource($model, $item, $sourceCode);
                } else {
                    $inventory = $this->getStock($model, $item, $stockId);
                }
                if ($inventory['is_salable'] > 0) {
                    if ($item->getTypeId() == 'configurable') {
                        $qty = $inventory['quantity'];
                    } else {
                        $qty = $inventory['quantity'];
                    }
                    if ($qty > 0) {
                        $value = $inStock;
                    } else {
                        if ($item->getBackorders() || $item->getUseConfigBackorders() && $model->backorders) {
                            $value = $availableForOrder;
                        } else {
                            $value = $outOfStock;
                        }
                    }
                } else {
                    $value = $outOfStock;
                }
            }
        } else {
            $value = $inStock;
        }
        return $value;
    }
    /**
     * {stock_status} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return string quantity of the product
     */
    public function isInStock($model, $options, $product, $reference)
    {
        $inStock = !isset($options['in_stock']) ? 'in stock' : $options['in_stock'];
        $outOfStock = !isset($options['out_of_stock']) ? 'out of stock' : $options['out_of_stock'];
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $stockId = $this->getStockId($model);
        $stockId = !isset($options['stock_id']) ? $stockId : $options['stock_id'];
        $sourceCode = !isset($options['source_code']) ? false : $options['source_code'];
        if (!$this->helperData->isMsiEnabled()) {
            $value = $item->getIs_in_stock() > 0 ? $inStock : $outOfStock;
        } else {
            if ($sourceCode) {
                $inventory = $this->getSource($model, $item, $sourceCode);
            } else {
                $inventory = $this->getStock($model, $item, $stockId);
            }
            $value = $inventory['is_salable'] > 0 ? $inStock : $outOfStock;
        }
        return $value;
    }
    /**
     * {qty} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return string quantity of the product
     */
    public function qty($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $float = !isset($options['float']) ? 0 : $options['float'];
        $stockId = $this->getStockId($model);
        $stockId = !isset($options['stock_id']) ? $stockId : $options['stock_id'];
        $sourceCode = !isset($options['source_code']) ? false : $options['source_code'];
        if (!$this->helperData->isMsiEnabled()) {
            if ($product->getTypeId() == 'configurable') {
                if (!isset($model->configurableQty[$product->getId()])) {
                    // configurable product without child
                    $value = number_format($item->getQty(), $float, '.', '');
                } else {
                    $value = number_format($model->configurableQty[$product->getId()], $float, '.', '');
                }
            } elseif ($reference == 'configurable') {
                $value = number_format($model->configurableQty[$item->getId()], $float, '.', '');
            } else {
                $value = number_format($item->getQty(), $float, '.', '');
            }
        } else {
            if ($sourceCode) {
                $inventory = $this->getSource($model, $item, $sourceCode);
            } else {
                $inventory = $this->getStock($model, $item, $stockId);
            }
            if ($product->getTypeId() == 'configurable') {
                $value = number_format($inventory['quantity'], $float, '.', '');
            } else {
                $value = number_format($inventory['quantity'], $float, '.', '');
            }
        }
        return $value;
    }
    /**
     * @param $attributeCall
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return null
     */
    public function proceedGeneric($attributeCall, $model, $options, $product, $reference)
    {
        return null;
    }
}