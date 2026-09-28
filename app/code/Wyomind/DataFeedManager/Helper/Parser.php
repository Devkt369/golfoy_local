<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Helper;

/**
 * Class Parser
 * @package Wyomind\DataFeedManager\Helper
 */
class Parser extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $attributeOptions;
    /**
     * @var string
     */
    protected $_regexAttributeCalls = "
            /(
                (?<attribute_calls>{{.*}})
              |
                (?<start_php>\\<\\?php)
              |
                (?<end_php>\\?\\>)
              )/xsU";
    /**
     * @var string
     */
    protected $_regexInsideAttributeCalls = "
        /(?<pattern>
            \\s*
            (?<object>[a-zA-Z_]+)
            \\.
            (?<property>[a-zA-Z0-9_]+)
            \\s?
            (?:
                \\s*
                (?<parameters>[^\\|}]*)
            )?
            \\s*
            (?<or>\\|)?
        )/sx";
    /**
     * @var string
     */
    protected $_regexParameters = '/(?<name>\\b\\w+\\b)\\s*=\\s*(?<value>"[^"]*"|\'[^\']*\'|[^"\'<>\\s]+)/';
    /**
     * @var string
     */
    protected $_regexIf = "
                /\\s*
                (
                    (
                        (?<object>(product|parent|configurable|bundle|grouped))
                        \\.
                        (?<property>[a-z0-9_]+)
                    ) 
                    |
                    (?<alias>[^\\.]+)
                )
                \\s?
                (?<condition>(<|>|<=|>=|==|!=))
                \\s*
                (?<value>[^=]+)
                \\s*
                /xs";
    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(\Magento\Framework\App\Helper\Context $context)
    {
        parent::__construct($context);
        $priceAttributesOptions = ['currency', 'vat_rate'];
        $this->attributeOptions = ['price' => $priceAttributesOptions, 'normal_price' => $priceAttributesOptions, 'final_price' => $priceAttributesOptions, 'special_price' => $priceAttributesOptions, 'price_rules' => $priceAttributesOptions, 'has_special_price' => ['yes', 'no'], 'min_price' => $priceAttributesOptions, 'max_price' => $priceAttributesOptions, 'image_link' => ['index'], 'qty' => ['float'], 'is_in_stock' => ['in_stock', 'out_of_stock', 'backorderable'], 'categories' => ['nb_path', 'from_level', 'nb_cat_in_each_path', 'path_separator', 'cat_separator'], 'category_mapping' => ['index'], 'SC:EAN' => ['index'], 'SC:URL' => ['html', 'index']];
    }
    /**
     * @param string $template
     * @return array
     */
    public function extractAttributeCalls($template)
    {
        $result = [];
        $matches = [];
        // first step : get all occurrences of {{....}}
        preg_match_all($this->_regexAttributeCalls, $template, $matches, PREG_OFFSET_CAPTURE);
        //        var_dump($matches);
        //        die();
        $attributeCalls = $matches['attribute_calls'];
        $startPhp = $matches['start_php'];
        $endPhp = $matches['end_php'];
        $count = max([count($attributeCalls), count($startPhp), count($endPhp)]);
        for ($i = 0; $i < $count; $i++) {
            if (!isset($attributeCalls[$i][0]) || $attributeCalls[$i][0] == "") {
                if (isset($startPhp[$i][0]) && $startPhp[$i][0] != "") {
                    $attributeCalls[$i] = $startPhp[$i];
                } elseif (isset($endPhp[$i][0]) && $endPhp[$i][0] != "") {
                    $attributeCalls[$i] = $endPhp[$i];
                }
            }
        }
        $inPhp = false;
        $call = 0;
        foreach ($attributeCalls as $attributeCall) {
            $position = $attributeCall[1];
            $attributeCall = $attributeCall[0];
            $call++;
            if ($attributeCall == "<?php") {
                $inPhp = true;
                continue;
            } elseif ($attributeCall == "?>") {
                $inPhp = false;
                continue;
            }
            $matchesTwo = [];
            // second step : parse the content of {{....}}
            $into = str_replace("\\\"", "\"", (string) $attributeCall);
            $into = str_replace("\\\\", "\\", (string) $into);
            preg_match_all($this->_regexInsideAttributeCalls, $into, $matchesTwo);
            $objects = $matchesTwo['object'];
            $properties = $matchesTwo['property'];
            $parameters = $matchesTwo['parameters'];
            $ors = $matchesTwo['or'];
            $i = 0;
            $originalAttributeCall = $attributeCall;
            if ($inPhp) {
                $newAttributeCall = str_replace(['{{', '}}'], ['[[', ']]'], (string) $attributeCall);
                $template = substr_replace($template, $newAttributeCall, $position, strlen($attributeCall));
                $attributeCall = 'PHP_' . $newAttributeCall;
            }
            $attributeCall = $call . '_' . $attributeCall;
            $result[$attributeCall] = [];
            foreach ($objects as $object) {
                $tmp = ['originalCall' => $originalAttributeCall];
                $tmp['object'] = $object;
                $tmp['property'] = $properties[$i];
                $parametersTmp = trim((string) $parameters[$i]);
                if ($parametersTmp != '') {
                    $matchesThree = [];
                    // third step : parse the parameters value="xxx"
                    preg_match_all($this->_regexParameters, $parametersTmp, $matchesThree);
                    $names = $matchesThree['name'];
                    $values = $matchesThree['value'];
                    $j = 0;
                    foreach ($names as $name) {
                        if ($name == 'if') {
                            $ifMatches = [];
                            preg_match_all($this->_regexIf, trim((string) $values[$j], "\"'"), $ifMatches);
                            if (isset($ifMatches['object'])) {
                                if (isset($ifMatches['alias']) && $ifMatches['alias'][0] != '') {
                                    $if = ['alias' => $ifMatches['alias'][0]];
                                } else {
                                    $if = ['object' => $ifMatches['object'][0], 'property' => $ifMatches['property'][0]];
                                }
                                $if['condition'] = $ifMatches['condition'][0];
                                $if['value'] = $ifMatches['value'][0];
                                if (!isset($tmp['parameters']["if"])) {
                                    $tmp['parameters']['if'] = [$if];
                                } else {
                                    $tmp['parameters']['if'][] = $if;
                                }
                            }
                        } else {
                            $toTrim = $values[$j];
                            $start = substr($toTrim, 0, 1);
                            $end = substr($toTrim, -1);
                            if ($start == $end && ($end == "'" || $end == '"')) {
                                $tmp['parameters'][$name] = substr($toTrim, 1, -1);
                            } else {
                                $tmp['parameters'][$name] = $toTrim;
                            }
                        }
                        $j++;
                    }
                }
                if (!isset($tmp['parameters'])) {
                    $tmp['parameters'] = '';
                }
                $tmp['or'] = $ors[$i] == '|';
                $result[$attributeCall][] = $tmp;
                $i++;
            }
        }
        return [$result, $template];
    }
}