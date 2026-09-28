<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Helper;

/**
 * Attributes management
 */
class Attributes extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var array
     */
    protected $_ignored_attribute_codes = ['status', 'visibility'];
    /**
     * @var array
     */
    protected $_ignored_attribute_backend_models = ['Magento\\Catalog\\Model\\Product\\Attribute\\Backend\\Price' => 'Wyomind\\DataFeedManager\\Helper\\AttributesPrices', 'Magento\\Catalog\\Model\\Product\\Attribute\\Backend\\Tierprice' => 'Wyomind\\DataFeedManager\\Helper\\AttributesPrices', 'Magento\\Weee\\Model\\Attribute\\Backend\\Weee\\Tax' => 'Wyomind\\DataFeedManager\\Helper\\AttributesWeeeTax'];
    /**
     * @var array
     */
    protected $_attributes = ['Wyomind\\DataFeedManager\\Helper\\AttributesDefault', 'Wyomind\\DataFeedManager\\Helper\\AttributesCategories', 'Wyomind\\DataFeedManager\\Helper\\AttributesImages', 'Wyomind\\DataFeedManager\\Helper\\AttributesInventory', 'Wyomind\\DataFeedManager\\Helper\\AttributesPrices', 'Wyomind\\DataFeedManager\\Helper\\AttributesStockInTheChannel', 'Wyomind\\DataFeedManager\\Helper\\AttributesUrl', 'Wyomind\\DataFeedManager\\Helper\\AttributesReviews', 'Wyomind\\DataFeedManager\\Helper\\AttributesChildren'];
    /**
     * @var array
     */
    protected $_listOfAttributes = [];
    /**
     * @var array
     */
    protected $_customVariables = [];
    public $_attributeSetRepository = null;
    public $_attributesCustomOptions = null;
    public $_messageManager = null;
    public $objectManager = null;
    /**
     * @var array
     */
    private $_as = [];
    /**
     * @var bool
     */
    public $skipProduct = false;
    /**
     * @var null
     */
    private $_model = null;
    public function __construct(\Wyomind\DataFeedManager\Helper\Delegate $wyomind, \Magento\Framework\App\Helper\Context $context, \Magento\Eav\Model\Entity\TypeFactory $attributeTypeFactory, \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory, \Wyomind\DataFeedManager\Model\ResourceModel\Variables\CollectionFactory $variableCollectionFactory)
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        parent::__construct($context);
        $typeId = -1;
        $resTypeId = $attributeTypeFactory->create()->getCollection()->addFieldToFilter('entity_type_code', ['eq' => 'catalog_product']);
        foreach ($resTypeId as $re) {
            $typeId = $re['entity_type_id'];
        }
        $attributesList = $attributeFactory->create()->getCollection()->addFieldToFilter('entity_type_id', ['eq' => $typeId]);
        $this->_listOfAttributes = [];
        foreach ($attributesList as $key => $attr) {
            $this->_listOfAttributes[$attr['attribute_code']] = $attr['backend_model'] ?: '';
        }
        $collection = $variableCollectionFactory->create();
        $this->_customVariables = [];
        foreach ($collection as $variable) {
            $this->_customVariables[$variable->getName()] = $variable->getScript();
        }
    }
    /**
     * @param $model
     */
    public function setModel($model)
    {
        $this->_model = $model;
    }
    /**
     * @param $isPreview
     * @param $output
     * @param $product
     * @return mixed|void
     * @throws \Exception
     */
    public function executePhpScripts($isPreview, $output, $product)
    {
        if ($output == null) {
            return;
        }
        $matches = [];
        preg_match_all("/(?<script><\\?php(?<php>.*)\\?>)/sU", $output, $matches);
        $i = 0;
        foreach (array_values($matches['php']) as $phpCode) {
            $val = null;
            $phpCode = str_replace(['\\\\', '\\"'], ['\\', '"'], (string) $phpCode);
            $displayErrors = ini_get('display_errors');
            ini_set('display_errors', 1);
            try {
                $val = $this->execPhp($phpCode, $phpCode, $product);
            } catch (\Exception $e) {
                ini_set('display_errors', $displayErrors);
                throw new \Exception('Syntax error in ' . $phpCode . ' :' . $e->getMessage());
            }
            ini_set('display_errors', $displayErrors);
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $output = str_replace($matches['script'][$i], (string) $val, $output);
            $i++;
        }
        return $output;
    }
    /**
     * @param $attributeCall
     * @param $product
     * @return array|bool|int|mixed|string
     * @throws \Exception
     */
    public function executeAttribute($attributeCall, $product)
    {
        if (is_array($attributeCall['parameters'])) {
            if (isset($attributeCall['parameters']['if'])) {
                $ifResult = true;
                foreach ($attributeCall['parameters']['if'] as $if) {
                    if (isset($if['alias'])) {
                        $prop = $this->_as[$if['alias']];
                    } elseif (isset($if['object'])) {
                        $prop = $this->proceed($if, [], $product);
                    } else {
                        $prop = '';
                    }
                    switch ($if['condition']) {
                        case '==':
                            $ifResult &= $prop == $if['value'];
                            break;
                        case '!=':
                            $ifResult &= $prop != $if['value'];
                            break;
                        case '>':
                            // return (float) $prop .">". (float) $if['value'];
                            $ifResult &= (float) $prop > (float) $if['value'];
                            break;
                        case '<':
                            $ifResult &= (float) $prop < (float) $if['value'];
                            break;
                        case '>=':
                            $ifResult &= (float) $prop >= (float) $if['value'];
                            break;
                        case '<=':
                            $ifResult &= (float) $prop <= (float) $if['value'];
                            break;
                    }
                }
                if (!$ifResult) {
                    return '';
                }
            }
        }
        // retrieve the main value
        $value = $this->proceed($attributeCall, $attributeCall['parameters'], $product);
        if (isset($attributeCall['parameters']['as'])) {
            $this->_as[$attributeCall['parameters']['as']] = $value;
        }
        $prefix = !isset($attributeCall['parameters']['prefix']) ? '' : $attributeCall['parameters']['prefix'];
        $suffix = !isset($attributeCall['parameters']['suffix']) ? '' : $attributeCall['parameters']['suffix'];
        // apply php
        if (is_array($attributeCall['parameters'])) {
            if (isset($attributeCall['parameters']['output'])) {
                if ($attributeCall['parameters']['output'] == "null") {
                    return '';
                }
                if (!is_array($value)) {
                    $toExecute = str_replace('$self', "stripslashes(\"" . str_replace('$', "\\\$", addslashes((string) $value)) . "\")", (string) $attributeCall['parameters']['output']);
                } else {
                    $toExecute = str_replace('$self', '$value', (string) $attributeCall['parameters']['output']);
                }
                if (is_numeric($toExecute)) {
                    $value = $toExecute;
                } else {
                    $value = $this->execPhp($attributeCall["originalCall"], "return " . $toExecute . ";", $product, $value);
                }
                if ($value === false) {
                    $this->skip();
                }
            }
        }
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $value = $value != '' ? $prefix . $value . $suffix : $value;
        return $value;
    }
    /**
     * @param bool $skip
     */
    public function skip($skip = true)
    {
        $this->skipProduct = $skip;
    }
    /**
     * @return bool
     */
    public function getSkip()
    {
        return $this->skipProduct;
    }
    /**
     * @param $product
     * @param string $type
     * @return mixed
     */
    public function hasParent($product, $type = 'parent')
    {
        return $this->_model->checkReference($type, $product);
    }
    /**
     * @param $product
     * @param string $type
     * @param bool $strict
     * @return mixed
     */
    public function getParent($product, $type = 'parent', $strict = false)
    {
        $item = $this->_model->checkReference($type, $product);
        if ($item == null && !$strict) {
            return $product;
        }
        return $item;
    }
    /**
     * @param $originalCall
     * @param $script
     * @param null $product
     * @param string $value
     * @return mixed
     * @throws \Exception
     */
    public function execPhp($originalCall, $script, $product = null, $value = '')
    {
        foreach ($this->_as as $key => $value) {
            ${$key} = $value;
        }
        try {
            return eval($script);
        } catch (\Throwable $e) {
            if ($product != null) {
                $exc = new \Exception("
Error on line:
" . $originalCall . "

Executed script:
" . $script . "

Error message:
" . $e->getMessage() . "

Product:
&nbsp;&nbsp;- ID: " . $product->getId() . "
&nbsp;&nbsp;- SKU: " . $product->getData('sku'));
            } else {
                $exc = new \Exception("
Error on line:
" . $originalCall . "

Executed script:
" . $script . "

Error message:
" . $e->getMessage());
            }
            throw $exc;
        }
    }
    /**
     * @param $product
     * @param $options
     * @param $productPattern
     * @return mixed|string|void
     */
    public function loadOptions($product, $options, $productPattern)
    {
        return $this->_attributesCustomOptions->loadOptions($this->_model, $product, $options, $productPattern);
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function id($model, $options, $product, $reference)
    {
        unset($options);
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $value = $item->getId();
        return $value;
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed
     */
    public function inc($model, $options, $product, $reference)
    {
        unset($options);
        unset($product);
        unset($reference);
        return $model->inc;
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return string
     */
    public function status($model, $options, $product, $reference)
    {
        unset($options);
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        return $item->getStatus();
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function attributeSet($model, $options, $product, $reference)
    {
        unset($options);
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $attributeSetId = $item->getAttributeSetId();
        $attributeSet = $this->_attributeSetRepository->get($attributeSetId);
        if ($attributeSet != null) {
            return $attributeSet->getAttributeSetName();
        } else {
            return '';
        }
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function typeId($model, $options, $product, $reference)
    {
        unset($options);
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $typeId = $item->getTypeId();
        return $typeId;
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function taxClass($model, $options, $product, $reference)
    {
        unset($options);
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $taxClassId = $item->getTaxClassId();
        if ($taxClassId != null) {
            return $model->taxRates[$taxClassId][0]["name"];
        } else {
            return '';
        }
    }
    /**
     * @param $model
     * @param $options
     * @param $product
     * @param $reference
     * @return mixed|string
     */
    public function relationShip($model, $options, $product, $reference)
    {
        $value = '';
        $item = $model->checkReference($reference, $product);
        if ($item == null) {
            return $value;
        }
        $separator = !isset($options['separator']) ? ',' : $options['separator'];
        if (isset($model->productRelationShip[$item->getId()])) {
            $value = str_replace('>>>', $separator, (string) $model->productRelationShip[$item->getId()]);
        }
        return $value;
    }
    /**
     * @param $attribute
     * @return bool
     */
    public function isProductAttribute($attribute)
    {
        if (!isset($this->_listOfAttributes[$attribute]) || in_array($attribute, $this->_ignored_attribute_codes) || isset($this->_listOfAttributes[$attribute]) && in_array($this->_listOfAttributes[$attribute], array_keys($this->_ignored_attribute_backend_models))) {
            return false;
        } else {
            return true;
        }
    }
    /**
     * @param $method
     * @return bool
     */
    public function exists($method)
    {
        foreach ($this->_attributes as $library) {
            if (method_exists($library, $method)) {
                return true;
            }
        }
        return false;
    }
    /**
     * @param $attributeCall
     * @param $options
     * @param $product
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public function proceed($attributeCall, $options, $product)
    {
        $reference = $attributeCall['object'];
        // product attributes
        if ($this->isProductAttribute($attributeCall['property'])) {
            return $this->productAttribute($attributeCall['property'], $product, $reference);
        } else {
            $exploded = explode('_', (string) $attributeCall['property']);
            $method = '';
            foreach ($exploded as $x) {
                $method .= ucfirst(strtolower($x));
            }
            $method = lcfirst($method);
            // specific backend models
            if (isset($this->_listOfAttributes[$attributeCall['property']])) {
                $backendModel = $this->_listOfAttributes[$attributeCall['property']];
                if (isset($this->_ignored_attribute_backend_models[$backendModel])) {
                    $backendModelHelper = $this->_ignored_attribute_backend_models[$backendModel];
                    if (method_exists($backendModelHelper, $method)) {
                        return $this->objectManager->get($backendModelHelper)->{$method}($this->_model, $options, $product, $reference);
                    } else {
                        return $this->objectManager->get($backendModelHelper)->proceedGeneric($attributeCall, $this->_model, $options, $product, $reference);
                    }
                }
            }
            // internal variables
            if (method_exists($this, $method)) {
                return $this->{$method}($this->_model, $options, $product, $reference);
            } else {
                foreach ($this->_attributes as $library) {
                    if (method_exists($library, $method)) {
                        return $this->objectManager->get($library)->{$method}($this->_model, $options, $product, $reference);
                    }
                }
            }
            // product custom options merge
            if ($attributeCall['object'] == 'custom_options' && $attributeCall['property'] == 'merge') {
                return $this->_attributesCustomOptions->merge($this->_model, $options, $product, $reference);
            }
            // custom variables
            if (array_key_exists($attributeCall['property'], $this->_customVariables)) {
                $product = $this->_model->checkReference($reference, $product);
                if ($product == null) {
                    return '';
                }
                $toExecute = trim((string) $this->_customVariables[$attributeCall['property']]);
                $toExecute = str_replace(['<?php', '?>'], '', (string) $toExecute);
                $value = $this->execPhp($toExecute, $toExecute, $product);
                return $value;
            }
        }
        return false;
    }
    /**
     * All other attributes processing
     * @param string $attribute
     * @param \Magento\Catalog\Model\Product $product
     * @param string $reference
     * @return array|mixed|string
     */
    public function productAttribute($attribute, $product, $reference)
    {
        $item = $this->_model->checkReference($reference, $product);
        if ($item == null) {
            return '';
        }
        $exploded = explode('_', (string) $attribute);
        $method = "";
        foreach ($exploded as $x) {
            $method .= ucfirst(strtolower($x));
        }
        $methodName = 'get' . str_replace(' ', '', ucfirst(trim((string) $method)));
        if (in_array($attribute, $this->_model->listOfAttributes)) {
            if (in_array($this->_model->listOfAttributesType[$attribute], ['select', 'multiselect'])) {
                $val = $item->{$methodName}();
                if ($val == '') {
                    $val = $item->getData($attribute);
                }
                $vals = explode(',', (string) $val);
                /* multiselect */
                if (count($vals) > 1) {
                    $value = [];
                    foreach ($vals as $v) {
                        if (isset($this->_model->attributesLabelsList[$v][$this->_model->params['store_id']])) {
                            $value[] = $this->_model->attributesLabelsList[$v][$this->_model->params['store_id']];
                        } else {
                            if (isset($this->_model->attributesLabelsList[$v][0])) {
                                $value[] = $this->_model->attributesLabelsList[$v][0];
                            }
                        }
                    }
                } else {
                    /* select */
                    if (isset($this->_model->attributesLabelsList[$vals[0]][$this->_model->params['store_id']])) {
                        $value = $this->_model->attributesLabelsList[$vals[0]][$this->_model->params['store_id']];
                    } else {
                        if (isset($this->_model->attributesLabelsList[$vals[0]][0])) {
                            $value = $this->_model->attributesLabelsList[$vals[0]][0];
                        }
                    }
                }
            } else {
                $value = $item->{$methodName}();
                if ($value == '') {
                    $value = $item->getData($attribute);
                }
            }
        }
        // Get currency value
        if (isset($this->_model->listOfCurrencies[$attribute])) {
            $value = $this->_model->listOfCurrencies[$attribute];
        }
        if (!isset($value)) {
            $value = '';
        }
        // Remove invalid characters
        $valueCleaned = preg_replace('/' . '[\\x00-\\x1F\\x7F]' . '|[\\x00-\\x7F][\\x80-\\xBF]+' . '|([\\xC0\\xC1]|[\\xF0-\\xFF])[\\x80-\\xBF]*' . '|[\\xC2-\\xDF]((?![\\x80-\\xBF])|[\\x80-\\xBF]{2,})' . '|[\\xE0-\\xEF](([\\x80-\\xBF](?![\\x80-\\xBF]))|' . '(?![\\x80-\\xBF]{2})|[\\x80-\\xBF]{3,})' . '/S', ' ', $value);
        if (is_array($valueCleaned)) {
            $value = str_replace('&#153;', '', implode(',', $valueCleaned));
        } else {
            $value = str_replace('&#153;', '', (string) $valueCleaned);
        }
        return $value;
    }
    /**
     * Compare two arrays
     * @param array $a
     * @param array $b
     * @return int
     */
    public static function cmpArray($a, $b)
    {
        if (strlen(implode('', $a)) == strlen(implode('', $b))) {
            return 0;
        }
        return strlen(implode('', $a)) < strlen(implode('', $b)) ? -1 : 1;
    }
    /**
     * Compare two strings
     * @param string $a
     * @param string $b
     * @return int
     */
    public static function cmp($a, $b)
    {
        if (strlen($a) == strlen($b)) {
            return 0;
        }
        return strlen($a) < strlen($b) ? 1 : -1;
    }
}