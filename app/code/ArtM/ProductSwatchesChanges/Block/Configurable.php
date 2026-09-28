<?php

namespace ArtM\ProductSwatchesChanges\Block;

class Configurable extends \Magento\Swatches\Block\Product\Renderer\Configurable
{
    /**
     * Check if product is in stock
     *
     * @param int $pid
     * @return bool
     */
    protected function checkInStock($pid)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();        
        $StockState = $objectManager->get('\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku');
        $qtyOfAllVarient = 0;   
              
        $cproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($pid); 
        $vsku = $cproduct->getSku();
             
        $isManageStock = $cproduct->getExtensionAttributes()->getStockItem()->getManageStock();
        if ($isManageStock == 1) {
            $qtysrc = $StockState->execute($vsku);
            $tqty = 0;
            foreach ($qtysrc as $src) {
                $tqty += $src['qty'];
            }
            $qtyOfAllVarient += $tqty; 
        } else {
            $qtyOfAllVarient = 1;
        } 
        return $qtyOfAllVarient > 0;    
    }

    /**
     * Get configurable option IDs
     *
     * @param array $attributeData
     * @return array
     */
    protected function getConfigurableOptionsIdsNew(array $attributeData)
    {           
        $ids = [];
        foreach ($this->getAllowProducts() as $product) {
            foreach ($this->helper->getAllowAttributes($this->getProduct()) as $attribute) {
                if ($this->checkInStock($product['entity_id'])) {
                    $productAttribute = $attribute->getProductAttribute();
                    $productAttributeId = $productAttribute->getId();
                    if (isset($attributeData[$productAttributeId])) {
                        $ids[$product->getData($productAttribute->getAttributeCode())] = 1;
                    }
                }
            }
        }
        return array_keys($ids);
    }

    /**
     * Get swatch configuration with quantity in labels
     *
     * @return string
     */
    public function getJsonSwatchConfig()
    {
        $attributesData = $this->getSwatchAttributesData();
        $allOptionIds  = $this->getConfigurableOptionsIdsNew($attributesData);
        $swatchesData  = $this->swatchHelper->getSwatchesByOptionsId($allOptionIds);
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $eavConfig = $objectManager->get(\Magento\Eav\Model\Config::class);

        $config = [];

        foreach ($attributesData as $attributeId => $attributeDataArray) {
            if (isset($attributeDataArray['options']) && is_array($attributeDataArray['options'])) {
                
                $swatchOptions = $this->addSwatchDataForAttribute(
                    $attributeDataArray['options'],
                    $swatchesData,
                    $attributeDataArray
                );
                try {
                    $attribute = $eavConfig->getAttribute('catalog_product', $attributeId);
                    $attributeCode = strtolower($attribute->getAttributeCode());
                } catch (\Exception $e) {
                    $attributeCode = '';
                }

                // Skip qty for color swatches
                if ($attributeCode !== 'color') {
                    foreach ($swatchOptions as $optionId => &$optionData) {
                        $qty = 0;
                        foreach ($this->getAllowProducts() as $product) {
                            foreach ($this->helper->getAllowAttributes($this->getProduct()) as $attrObj) {
                                $attr = $attrObj->getProductAttribute();
                                if ($attr && $attr->getId() == $attributeId &&
                                    $product->getData($attr->getAttributeCode()) == $optionId && $this->getAttributesToShowQty($attr->getId())) {
                                    $qty = $this->getQtyOfAllVarient($product->getId());
                                    break 2;
                                }
                            }
                        }
                        $optionData['qty'] = $qty;
                        if ($qty > 0) {
                            $optionData['label'] .= '';
                        } else {
                            $optionData['label'] .= '';
                        }
                    }
                }

                $config[$attributeId] = $swatchOptions;

                if (isset($attributeDataArray['additional_data'])) {
                    $config[$attributeId]['additional_data'] = $attributeDataArray['additional_data'];
                }
            }
        }

        // print_r($config); die;

        return $this->jsonEncoder->encode($config);
    }


    /**
     * Get total quantity of a product variant
     *
     * @param int $pid
     * @return int
     */
    public function getQtyOfAllVarient($pid)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();        
        $StockState = $objectManager->get('\Magento\InventorySalesAdminUi\Model\GetSalableQuantityDataBySku');
        $qtyOfAllVarient = 0;   
              
        $cproduct = $objectManager->create('Magento\Catalog\Model\Product')->load($pid); 
        $vsku = $cproduct->getSku();
        
        $isManageStock = $cproduct->getExtensionAttributes()->getStockItem()->getManageStock();
        if ($isManageStock == 1) {
            $qtysrc = $StockState->execute($vsku);
            $tqty = 0;
            foreach ($qtysrc as $src) {
                if ($src['stock_id'] == 2) {
                    $tqty += $src['qty'];
                    continue;
                }
            }
            $qtyOfAllVarient += $tqty; 
        }
        return $qtyOfAllVarient;   
    }

    public function getAttributesToShowQty($attributeId)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $scopeConfig   = $objectManager->get(\Magento\Framework\App\Config\ScopeConfigInterface::class);
        $attributesData = $scopeConfig->getValue(
            'common/general/swatchqty_attributes',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
        if (!$attributesData) {
            return false;
        }
        $attributeIds = explode(",", $attributesData);
        return in_array($attributeId, $attributeIds);
    }

}