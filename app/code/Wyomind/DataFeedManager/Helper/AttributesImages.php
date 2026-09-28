<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Helper;

/**
 * Attributes management
 */
class AttributesImages extends \Magento\Framework\App\Helper\AbstractHelper implements \Wyomind\DataFeedManager\Helper\AttributesInterface
{
    public $_framework;
    public function __construct(\Wyomind\DataFeedManager\Helper\Delegate $wyomind, \Magento\Framework\App\Helper\Context $context)
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context);
    }
    /**
     * {image} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return string product's image
     */
    public function imageLink($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        $idCol = $this->_framework->moduleIsEnabled("Magento_Enterprise") ? "row_id" : "entity_id";
        if ($item == null) {
            return '';
        }
        $baseImage = $item->getImage();
        $value = '';
        $role = $options['role'] ?? false;
        if ($role) {
            $exploded = explode('_', (string) $role);
            $method = "";
            foreach ($exploded as $x) {
                $method .= ucfirst(strtolower($x));
            }
            $methodName = 'get' . str_replace([' ', '_'], '', ucfirst(trim($method)));
            $image = $item->{$methodName}();
            if ($image == "") {
                $image = $item->getData($role);
            }
            if ($image != "") {
                $path = 'catalog/product/' . $image;
                $value = $model->baseImg . str_replace('//', '/', (string) $path);
            }
        } elseif (!isset($options['index']) || $options['index'] == 0) {
            if ($item->getImage() != null && $item->getImage() != "" && $item->getImage() != 'no_selection') {
                $path = 'catalog/product/' . $item->getImage();
                $value = $model->baseImg . str_replace('//', '/', (string) $path);
            } else {
                if ($model->defaultImage != "") {
                    $value = $model->baseImg . '/catalog/product/placeholder/' . $model->defaultImage;
                }
            }
        } elseif (isset($model->gallery[$item->getData($idCol)]['src'][$options['index'] - 1]) && $options['index'] > 0) {
            if ($model->gallery[$item->getData($idCol)]['src'][$options['index'] - 1] != $baseImage) {
                $path = 'catalog/product/' . $model->gallery[$item->getData($idCol)]['src'][$options['index'] - 1];
                $value = $model->baseImg . str_replace('//', '/', (string) $path);
            }
        } elseif ($options['index'] < 0) {
            $reversedImages = array_reverse($model->gallery[$item->getData($idCol)]['src']);
            $index = $options['index'] * -1 - 1;
            if (isset($reversedImages[$index])) {
                $path = 'catalog/product/' . $reversedImages[$index];
                $value = $model->baseImg . str_replace('//', '/', (string) $path);
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