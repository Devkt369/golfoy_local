<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Helper;

/**
 * Attributes management
 */
class AttributesUrl extends \Magento\Framework\App\Helper\AbstractHelper implements \Wyomind\DataFeedManager\Helper\AttributesInterface
{
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed
     */
    public function host($model, $options, $product, $reference)
    {
        unset($options);
        unset($product);
        unset($reference);
        return $model->getStoreUrl();
    }

    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function urlConfig($model, $options, $product, $reference)
    {
        unset($reference);
        $parent = $model->checkReference('configurable', $product);
        if ($parent == null) {
            return '';
        }

        $attributes = $parent->getTypeInstance(true)->getConfigurableAttributes($parent);

        $currency = (isset($options['currency'])) ? $options['currency'] : null;
        $url = $this->url($model, $options, $product, 'configurable') . ($currency ? "&" : "?") . "ps="; // ps parameter is for Google Microdata
        $atts = [];
        foreach ($attributes as $attribute) {
            $att = $attribute->getProductAttribute()->getData('attribute_code');
            $id = $attribute->getProductAttribute()->getData('attribute_id');
            if ($product->getData($att) == "") {
                $product->load($product->getId());
            }
            $atts[] = $id . '=' . $product->getData($att);
        }

        return $url . base64_encode(implode('&', $atts)) . "#" . implode('&', $atts);
    }

    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function url($model, $options, $product, $reference)
    {
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }

        $currency = (isset($options['currency'])) ? $options['currency'] : null;

        if ($item->getRequest_path()) {
            // shortest
            if (null === $model->urlRewrites || $model->urlRewrites == \Wyomind\DataFeedManager\Model\Config\UrlRewrite::SHORTEST_URL) {
                $arr = explode(",", (string)$item->getRequest_path());
                usort($arr, ['\Wyomind\DataFeedManager\Helper\Attributes', 'cmp']);
                $value = $model->storeUrl . array_pop($arr);
            } elseif ($model->urlRewrites == \Wyomind\DataFeedManager\Model\Config\UrlRewrite::LONGEST_URL) { // longest
                $arr = explode(",", (string)$item->getRequest_path());
                usort($arr, ['\Wyomind\DataFeedManager\Helper\Attributes', 'cmp']);
                $value = $model->storeUrl . array_shift($arr);
            } else {
                $value = $model->storeUrl . $item->getRequest_path();
            }
        } else {
            $value = "";
        }
        if ($value == '') { // last fallback
            if ($item->getUrlKey() != '') {
                $value = $model->storeUrl . 'catalog/product/view/id/' . $item->getId();
            }
        }

        if ($value != "" && $currency) {
            $value .= "?currency=".$currency;
        }

        return $value;
    }

    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed|string
     */
    public function uri($model, $options, $product, $reference)
    {
        unset($options);
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }

        if ($item->getRequest_path()) {
            // shortest
            if ($model->urlRewrites == \Wyomind\DataFeedManager\Model\Config\UrlRewrite::SHORTEST_URL) {
                $arr = explode(",", (string)$item->getRequest_path());
                usort($arr, ['\Wyomind\DataFeedManager\Helper\Attributes', 'cmp']);
                $value = array_pop($arr);
            } elseif ($model->urlRewrites == \Wyomind\DataFeedManager\Model\Config\UrlRewrite::LONGEST_URL) { // longest
                $arr = explode(",", (string)$item->getRequest_path());
                usort($arr, ['\Wyomind\DataFeedManager\Helper\Attributes', 'cmp']);
                $value = array_shift($arr);
            } else {
                $value = $item->getRequest_path();
            }
        } else {
            $value = str_replace($model->storeUrl, '', (string)$item->getProductUrl());
        }

        return $value;
    }

    /**
     * @param $attributeCall
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed|null
     */
    public function proceedGeneric($attributeCall, $model, $options, $product, $reference)
    {
        return null;
    }
}
