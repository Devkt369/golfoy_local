<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Helper;

/**
 * Attributes management
 */
class AttributesCustomOptions extends \Magento\Framework\App\Helper\AbstractHelper implements \Wyomind\DataFeedManager\Helper\AttributesInterface
{
    /**
     * {load_options} attribute processing
     * Important : Only 2 custom options
     * @param $model
     * @param $product
     * @param $options
     * @param $productPattern
     * @return mixed|string|void
     */
    public function loadOptions($model, $product, $options, $productPattern)
    {
        if (!is_array($options)) {
            return;
        }

        $opts = [];
        $aliases = [];

        foreach ($options as $key => $option) {
            $opts[] = $option;
            $aliases[] = $key;
        }

        if (count($opts)) {
            $first = isset($model->customOptions[$product->getId()][$opts[0]]) ? $opts[0] : null;
            $firstAlias = $first != null ? $aliases[0] : null;
            if (count($opts) > 1) {
                $second = isset($model->customOptions[$product->getId()][$opts[1]]) ? $opts[1] : null;
                $secondAlias = $second != null ? $aliases[1] : null;
            }
            if (!$first && isset($second) && $second != null) {
                $first = $second;
                $second = null;
            }
            $pattern = [];
            if (isset($first) && $first != null) {
                $count = count($model->customOptions[$product->getId()][$first]['options']);
                if (isset($second) && $second != null) {
                    $countBis = count($model->customOptions[$product->getId()][$second]['options']);
                } else {
                    $countBis = 0;
                }
                for ($i = 0; $i < $count; $i++) {
                    $tempPattern = $productPattern;
                    $tempPattern = str_replace("{{custom_options." . $firstAlias . ".label}}", $model->customOptions[$product->getId()][$first]['options'][$i]['value'], $tempPattern);
                    $tempPattern = str_replace("{{custom_options." . $firstAlias . ".sku}}", $model->customOptions[$product->getId()][$first]['options'][$i]['sku'], $tempPattern);
                    $tempPattern = str_replace("{{custom_options." . $firstAlias . ".price}}", $model->customOptions[$product->getId()][$first]['options'][$i]['price'], $tempPattern);
                    $tempPattern = str_replace("{{custom_options." . $firstAlias . ".type}}", $model->customOptions[$product->getId()][$first]['options'][$i]['price_type'], $tempPattern);
                    if ($countBis != 0) {
                        for ($j = 0; $j < $countBis; $j++) {
                            $tempPatternBis = $tempPattern;
                            $tempPatternBis = str_replace("{{custom_options." . $secondAlias . ".label}}", $model->customOptions[$product->getId()][$second]['options'][$j]['value'], $tempPatternBis);
                            $tempPatternBis = str_replace("{{custom_options." . $secondAlias . ".sku}}", $model->customOptions[$product->getId()][$second]['options'][$j]['sku'], $tempPatternBis);
                            $tempPatternBis = str_replace("{{custom_options." . $secondAlias . ".price}}", $model->customOptions[$product->getId()][$second]['options'][$j]['price'], $tempPatternBis);
                            $tempPatternBis = str_replace("{{custom_options." . $secondAlias . ".type}}", $model->customOptions[$product->getId()][$second]['options'][$j]['price_type'], $tempPatternBis);
                            $pattern[] = $tempPatternBis;
                        }
                    } else {
                        $pattern[] = $tempPattern;
                    }
                }
                if (count($pattern) > 0) {
                    if ($model->getType() > 1) {
                        $columns = [];
                        $final = [];
                        foreach ($pattern as $json) {
                            $template = json_decode((string) $json, true);
                            $columns = $template["product"];
                            $columns[count($columns) - 1] .= "/breakline/";
                            $final = array_merge($final, $columns);
                        };
                        $productPattern = json_encode(["product" => $final]);
                    } else {
                        $productPattern = implode("\n", $pattern);
                    }
                }
//                $productPattern = implode("\n", $pattern);
            }
        }

        $tempPattern = $productPattern;
        foreach ($options as $key => $option) {
            $tempPattern = str_replace("{{" . $option . "_label}}", "", $tempPattern);
            $tempPattern = str_replace("{{" . $option . "_sku}}", "", $tempPattern);
            $tempPattern = str_replace("{{" . $option . "_price}}", "", $tempPattern);
            $tempPattern = str_replace("{{" . $option . "_type}}", "", $tempPattern);
            $tempPattern = str_replace("{{" . $option . "_id}}", "", $tempPattern);
        }


        return $tempPattern;
    }

    /**
     * {use_option} attribute processing
     * @param \Wyomind\DataFeedManager\Model\Feeds $model
     * @param array $options
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return string
     */
    public function merge($model, $options, $product, $reference)
    {
        unset($reference);
        if (isset($model->customOptions[$product->getId()][$options['opt']])) {
            $concat = [];

            foreach ($model->customOptions[$product->getId()][$options['opt']]['options'] as $o) {
                $concat[] = $o[$options['value']];
            }

            return implode(',', $concat);
        }

        return '';
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
