<?php
namespace Mango\Ajaxlayerednavigation\Model\Layer\Filter;

/**
 * Layer attribute filter
 */
class Attribute extends \Magento\CatalogSearch\Model\Layer\Filter\Attribute
{
    /**
     * @var \Magento\Framework\Filter\StripTags
     */
    private $tagFilter;
    protected $isFilterActive;
    protected $activeFilterItems = [];
    protected $scopeConfig;
    protected $showItemsNoCount = false;
    protected $helper;
    protected $resource;
    protected $isMysqlEngine = false;
    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Framework\Filter\StripTags $tagFilter
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Filter\StripTags $tagFilter,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\Framework\App\ResourceConnection $resource,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $tagFilter,
            $data
        );
        $this->helper = $helper;
        $this->tagFilter = $tagFilter;
        $this->scopeConfig = $scopeConfig;
        $this->resource = $resource;
        $this->showItemsNoCount = $this->scopeConfig->getValue('ajaxlist/general/show_no_count', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $this->isMysqlEngine = $this->scopeConfig->getValue('catalog/search/engine') == "mysql";
    }
    /**
     * Apply attribute option filter to product collection
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return parent::apply($request);
        }
        $attributeValue = $request->getParam($this->_requestVar);
        if (empty($attributeValue)) {
            return $this;
        }
        if (preg_match('/^[0-9,]+$/', $attributeValue)) {
            $attributeValue = array_unique(explode(',', $attributeValue));
        }
        $attribute = $this->getAttributeModel();
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()
                ->getProductCollection();
        $labels = $this->_getOptionText($attributeValue);
        if ($attributeValue && $labels) {
            if ($this->isMysqlEngine) {
            # check if filter is multiselect and should include AND condition
            if ($this->_isAndCondition($attribute->getAttributeCode())) {
                $connection = $this->resource->getConnection();
                $_count_values = count($attributeValue);
                $_table = $this->resource->getTableName("catalog_product_index_eav");
                $select = $connection->select();
                $select->from($_table)
                    ->columns(["entity_id"])
                    ->where("value in(?)", $attributeValue)
                    ->where("store_id = ?", $productCollection->getStoreId())
                    ->group("entity_id")
                    ->having("count(distinct value) = ?", $_count_values)
                ;
                $result = $connection->fetchCol($select);
                $this->helper->setMultiselectAndFilter($result);
                $productCollection->getSelect()->where('e.entity_id in (?)', $result);
            } else {
            $productCollection->addFieldToFilter($attribute->getAttributeCode(), ['in' => $attributeValue]);
                }
            } else {
                # elasticsearch : add the filter, the AND condition will be processed during the query generation
                $productCollection->addFieldToFilter($attribute->getAttributeCode(), $attributeValue);
            }
            foreach ($labels as $value => $label) {
                /* will used this variable in blocks , to look for active id's */
                $this->activeFilterItems[] = $value;
                $this->getLayer()
                        ->getState()
                        ->addFilter($this->_createItem($label, $value));
            }
            $this->isFilterActive = true;
        }
        return $this;
    }
    protected function _isAndCondition($code)
    {
        $config = explode(",", trim($this->scopeConfig->getValue('ajaxlist/frontend/and_filter', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)));
        return in_array($code, $config);
    }
    protected function _getOptionText($filter)
    {
        $attribute = $this->getAttributeModel();
        $optionText = [];

        foreach ((array)$filter as $optionId) {
            $optionText[$optionId] = $attribute->getFrontend()->getOption($optionId);
        }

        return $optionText;
    }
    /**
     * Get data array for building attribute filter items
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getItemsData()
    {
        
             /*   $debugBackTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
foreach ($debugBackTrace as $item) {
    echo @$item['class'] . @$item['type'] . @$item['function'] . "\n<br/>";
}
exit;*/    
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return parent::_getItemsData();
        }       
        $attribute = $this->getAttributeModel();
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()
                ->getProductCollection();
        $optionsFacetedData = $productCollection->getFacetedData($attribute->getAttributeCode());
        // process list of options only if faceted data or an item was selected...
        if (!empty($optionsFacetedData) || !empty($this->getActiveFilterItems())) {
            $filter_values = $this->getActiveFilterItems();
            $options = $attribute
                ->getFrontend()
                ->getSelectOptions();
            foreach ($options as $option) {
                $option_used = in_array($option["value"], $filter_values);
                if (empty($option['value'])) {
                    continue;
                }
                // Check filter type
                if (!$this->showItemsNoCount && (empty($optionsFacetedData[$option['value']]['count']) && ($this->getAttributeIsFilterable($attribute) == static::ATTRIBUTE_OPTIONS_ONLY_WITH_RESULTS) && !$option_used )
                ) {
                    continue;
                }
                if (!isset($optionsFacetedData[$option['value']])) {
                    $optionsFacetedData[$option['value']]['count'] = 0;
                }
                $this->itemDataBuilder->addItemData(
                    $this->tagFilter->filter($option['label']),
                    $option['value'],
                    $optionsFacetedData[$option['value']]['count']
                );
            }
        }
        return $this->itemDataBuilder->build();
    }
    public function isFilterActive()
    {
        return $this->isFilterActive;
    } 
    public function setIsFilterActive($value)
    {
        $this->isFilterActive = (bool)$value;
        return $this;
    }
    /* returns array */
    public function getActiveFilterItems()
    {
        return $this->activeFilterItems;
    }   
    public function setActiveFilterItems($newValue)
    {
        $this->activeFilterItems[] = $newValue;
        return $this;
    }
    
    public function getCode()
    {
        return $this->getAttributeModel()->getAttributeCode();
    }
    /**
     * Retrieve array with products counts per attribute option
     *
     * @param Mage_Catalog_Model_Layer_Filter_Attribute $filter
     * @return array
     */
    public function getCountMultiselect()
    {
        // $filter_values) {
        // clone select from collection with filters
        $select = clone $this->getLayer()
                ->getProductCollection()->getSelect();
        // reset columns, order and limitation conditions
        $select->reset(\Magento\Framework\DB\Select::COLUMNS);
        $select->reset(\Magento\Framework\DB\Select::ORDER);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_COUNT);
        $select->reset(\Magento\Framework\DB\Select::LIMIT_OFFSET);
        $connection = $this->resource->getConnection();
        $attribute = $this->getAttributeModel();
        $tableAlias = $attribute->getAttributeCode() . '_idy';
        #$filter_values= $this->getActiveFilterItems();
        /* if($attribute->getFrontendInput() == "multiselect"){
          print_r($filter_values);
          /*   print_r($attribute->toArray());
          } */
        $conditions = [
            "{$tableAlias}.entity_id = e.entity_id",
            $connection->quoteInto("{$tableAlias}.attribute_id = ?", $attribute->getAttributeId()),
            $connection->quoteInto("{$tableAlias}.store_id = ?", $this->getStoreId()),
        ];
        $_from = $select->getPart(\Magento\Framework\DB\Select::FROM);
        #$_all_conditions = $this->getConditions();
        /* foreach ($_from as $index => $condition) {
          if ($index == ($attribute->getAttributeCode() . "_idx"))
          unset($_from[$attribute->getAttributeCode() . "_idx"]);
          } */
        $_table = $this->resource->getTableName("catalog_product_index_eav");
        $select->setPart(\Magento\Framework\DB\Select::FROM, $_from);
        $select
            ->join(
                [$tableAlias => $_table],
                join(' AND ', $conditions),
                ['value', 'count' => "COUNT( distinct({$tableAlias}.entity_id))"]
            )
            ->group("{$tableAlias}.value");
        $select->distinct();
        #echo $select . "***";
        $info = $connection->fetchAll($select);
        /* echo "<pre>";
          print_r($info);
          echo "</pre>"; */
        $countInfo = [];
        foreach ($info as $data) {
            #print_r($data);
            $countInfo[$data['value']]['count'] = $data['count'];
        }
        #print_r($countInfo);
        return $countInfo;
    }
}
