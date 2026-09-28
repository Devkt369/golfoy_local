<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Model\ResourceModel\Product;

use Magento\Framework\DB\Select;
/**
 * Product collection
 */
class Collection extends \Magento\Catalog\Model\ResourceModel\Product\Collection
{
    const CATEGORIES_FILTER_PRODUCT = 0;
    const CATEGORIES_FILTER_PRODUCT_AND_PARENT = 1;
    const CATEGORIES_FILTER_PARENT = 2;
    const MAX_ATTRIBUTE = 25;
    /**
     * @var null|string
     */
    protected $_rowId = null;
    public $_framework = null;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Framework\Data\Collection\EntityFactory $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Validator\UniversalFactory $universalFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Catalog\Model\Indexer\Product\Flat\State $catalogProductFlatState,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\OptionFactory $productOptionFactory,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Api\GroupManagementInterface $groupManagement,
        /** @delegation off */
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        $this->_rowId = $this->_framework->moduleIsEnabled('Magento_Enterprise') ? 'row_id' : 'entity_id';
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $eavConfig, $resource, $eavEntityFactory, $resourceHelper, $universalFactory, $storeManager, $moduleManager, $catalogProductFlatState, $scopeConfig, $productOptionFactory, $catalogUrl, $localeDate, $customerSession, $dateTime, $groupManagement, $connection);
    }
    /**
     * @return boolean
     */
    public function isEnabledFlat()
    {
        return false;
    }
    /**
     * @param $storeId
     * @param $includeDisabled
     * @return array
     */
    public function getConfigurableQuantities($storeId, $includeDisabled)
    {
        $connection = $this->_resource;
        $tableCpsl = $connection->getTableName('catalog_product_super_link');
        $tableCsi = $connection->getTableName('cataloginventory_stock_item');
        $this->addStoreFilter($storeId);
        if (!$includeDisabled) {
            $this->addAttributeToFilter('status', '1');
        }
        $this->addAttributeToFilter('type_id', ['eq' => 'configurable'])->addAttributeToFilter("visibility", ['neq' => '1']);
        $this->getSelect()->joinLeft(['cpsl' => $tableCpsl], "cpsl.parent_id=e." . $this->_rowId . " ")->joinLeft(['stock' => $tableCsi], "stock.product_id=cpsl.product_id", ["qty" => "SUM(stock.qty)"])->group(['cpsl.parent_id']);
        $configurableQty = [];
        foreach ($this as $config) {
            $configurableQty[$config->getId()] = $config->getQty();
        }
        return $configurableQty;
    }
    /**
     * @param $storeId
     * @param $notLike
     * @param $concat
     * @param $listOfAttributes
     * @param $includeDisabled
     * @return array
     */
    public function getGroupedProducts($storeId, $notLike, $concat, $listOfAttributes, $includeDisabled)
    {
        $connection = $this->_resource;
        $tableCsi = $connection->getTableName('cataloginventory_stock_item');
        $tableCur = $connection->getTableName('url_rewrite');
        $tableCpe = $connection->getTableName('catalog_product_entity');
        $tableCcpi = $connection->getTableName('catalog_category_product');
        $tableCpl = $connection->getTableName('catalog_product_link');
        $this->addStoreFilter($storeId);
        if (!$includeDisabled) {
            $this->addAttributeToFilter('status', '1');
        }
        $this->addAttributeToFilter('type_id', ['eq' => 'grouped']);
        $this->addAttributeToFilter('visibility', ['neq' => 1]);
        $joinType = count($listOfAttributes) <= self::MAX_ATTRIBUTE;
        $this->addAttributeToSelect($listOfAttributes, $joinType);
        $this->getSelect()->joinLeft(['cpl' => $tableCpl], "cpl.product_id=e." . $this->_rowId . " AND cpl.link_type_id=3", ["child_ids" => "GROUP_CONCAT( DISTINCT cpl.linked_product_id)", "child_skus" => "GROUP_CONCAT( DISTINCT (SELECT sku FROM {$tableCpe} WHERE entity_id=cpl.linked_product_id LIMIT 1))"])->joinLeft(['stock' => $tableCsi], "stock.product_id=e.entity_id", ["qty" => "qty", "is_in_stock" => "is_in_stock", "manage_stock" => "manage_stock", "use_config_manage_stock" => "use_config_manage_stock", "backorders" => "backorders", "use_config_backorders" => "use_config_backorders"])->joinLeft(['url' => $tableCur], "url.entity_id=e.entity_id " . $notLike . " AND url.entity_type ='product' AND url.store_id=" . $storeId, ["request_path" => $concat . "(DISTINCT request_path)"])->joinLeft(['categories' => $tableCcpi], "categories.product_id=e.entity_id", ["categories_ids" => "GROUP_CONCAT( DISTINCT categories.category_id)"])->group(['cpl.product_id']);
        $grouped = [];
        $children = [];
        foreach ($this as $parent) {
            foreach (explode(',', (string) $parent->getChildIds()) as $childId) {
                $grouped[$childId] = $parent;
            }
            $children[$parent->getId()] = ["id" => explode(',', (string) $parent->getChildIds()), "sku" => explode(',', (string) $parent->getChildSkus())];
        }
        return [$grouped, $children];
    }
    /**
     * @param $type
     * @param $storeId
     * @param $notLike
     * @param $concat
     * @param $listOfAttributes
     * @param $includeDisabled
     * @return array
     */
    protected function getParentProducts($type, $storeId, $notLike, $concat, $listOfAttributes, $includeDisabled)
    {
        $connection = $this->_resource;
        $tableCsi = $connection->getTableName('cataloginventory_stock_item');
        $tableCcpi = $connection->getTableName('catalog_category_product');
        $tableCpsl = $connection->getTableName('catalog_product_super_link');
        $tableCur = $connection->getTableName('url_rewrite');
        $tableCurpc = $connection->getTableName('catalog_url_rewrite_product_category');
        $tableCpr = $connection->getTableName('catalog_product_relation');
        $tableCpe = $connection->getTableName('catalog_product_entity');
        $this->addStoreFilter($storeId);
        if (!$includeDisabled) {
            $this->addAttributeToFilter('status', '1');
        }
        $joinType = count($listOfAttributes) <= self::MAX_ATTRIBUTE;
        $this->addAttributeToFilter('type_id', ['eq' => $type])->addAttributeToFilter('visibility', ['neq' => '1'])->addAttributeToSelect($listOfAttributes, $joinType);
        if ($type == 'bundle') {
            $this->getSelect()->joinLeft(['cpsl' => $tableCpr], "cpsl.parent_id=e." . $this->_rowId . " ", ["child_ids" => "GROUP_CONCAT( DISTINCT cpsl.child_id)", "child_skus" => "GROUP_CONCAT( DISTINCT (SELECT sku FROM {$tableCpe} WHERE entity_id=cpsl.child_id  LIMIT 1))"]);
        } else {
            $this->getSelect()->joinLeft(['cpsl' => $tableCpsl], "cpsl.parent_id=e." . $this->_rowId . " ", ["child_ids" => "GROUP_CONCAT( DISTINCT cpsl.product_id)", "child_skus" => "GROUP_CONCAT( DISTINCT (SELECT sku FROM {$tableCpe} WHERE entity_id=cpsl.product_id  LIMIT 1))"]);
        }
        $this->getSelect()->joinLeft(['stock' => $tableCsi], "stock.product_id=e.entity_id", ["qty" => "qty", "is_in_stock" => "is_in_stock", "manage_stock" => "manage_stock", "use_config_manage_stock" => "use_config_manage_stock", "backorders" => "backorders", "use_config_backorders" => "use_config_backorders"])->joinLeft(['url' => $tableCur], "url.entity_id=e.entity_id " . $notLike . " AND url.entity_type ='product' AND url.store_id=" . $storeId, ["request_path" => $concat . "(DISTINCT request_path)"])->joinLeft(['curpc' => $tableCurpc], "url.url_rewrite_id=curpc.url_rewrite_id and e.entity_id = curpc.product_id")->joinLeft(['categories' => $tableCcpi], "categories.product_id=e.entity_id", ["categories_ids" => "GROUP_CONCAT( DISTINCT categories.category_id)"])->group(['cpsl.parent_id']);
        $parent = [];
        $children = [];
        foreach ($this as $p) {
            foreach (explode(',', (string) $p->getChildIds()) as $childId) {
                $parent[$childId] = $p;
            }
            $children[$p->getId()] = ["id" => explode(',', (string) $p->getChildIds()), "sku" => explode(',', (string) $p->getChildSkus())];
        }
        return [$parent, $children];
    }
    /**
     * @param $storeId
     * @param $notLike
     * @param $concat
     * @param $listOfAttributes
     * @param $includeDisabled
     * @return array
     */
    public function getBundleProducts($storeId, $notLike, $concat, $listOfAttributes, $includeDisabled)
    {
        return $this->getParentProducts('bundle', $storeId, $notLike, $concat, $listOfAttributes, $includeDisabled);
    }
    /**
     * @param $storeId
     * @param $notLike
     * @param $concat
     * @param $listOfAttributes
     * @param $includeDisabled
     * @return array
     */
    public function getConfigurableProducts($storeId, $notLike, $concat, $listOfAttributes, $includeDisabled)
    {
        return $this->getParentProducts('configurable', $storeId, $notLike, $concat, $listOfAttributes, $includeDisabled);
    }
    /**
     * @param $storeId
     * @param $websiteId
     * @param $notLike
     * @param $concat
     * @param $manageStock
     * @param $listOfAttributes
     * @param $categoriesFilterList
     * @param $condition
     * @param $params
     * @param $includeDisabled
     * @return mixed
     * @throws \Exception
     */
    public function getProductCount($storeId, $websiteId, $notLike, $concat, $manageStock, $listOfAttributes, $categoriesFilterList, $condition, $params, $includeDisabled)
    {
        $this->getMainRequest($storeId, $websiteId, $notLike, $concat, $manageStock, $listOfAttributes, $categoriesFilterList, $condition, $params, $includeDisabled);
        $this->getSelect()->columns("COUNT(DISTINCT e.entity_id) As total");
        $this->getSelect()->limit(1);
        $this->getSelect()->reset(Select::GROUP);
        return $this->getFirstItem()->getTotal();
    }
    /**
     * @param $sqlSize
     * @param $loop
     */
    public function setLimit($sqlSize, $loop)
    {
        $this->getSelect()->limit($sqlSize, $sqlSize * $loop);
    }
    /**
     * @param $storeId
     * @param $websiteId
     * @param $notLike
     * @param $concat
     * @param $manageStock
     * @param $listOfAttributes
     * @param $categoriesFilterList
     * @param $condition
     * @param $params
     * @param $includeDisabled
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMainRequest($storeId, $websiteId, $notLike, $concat, $manageStock, $listOfAttributes, $categoriesFilterList, $condition, $params, $includeDisabled)
    {
        $storeManager = $this->_storeManager;
        $storeRootId = $storeManager->getStore($params['store_id'])->getRootCategoryId();
        $categoryRootId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        $connection = $this->_resource;
        $tableCpsl = $connection->getTableName('catalog_product_super_link');
        $tableCpe = $connection->getTableName("catalog_product_entity");
        $tableCce = $connection->getTableName("catalog_category_entity");
        $tableCsi = $connection->getTableName('cataloginventory_stock_item');
        $tableCur = $connection->getTableName('url_rewrite');
        $tableCcpi = $connection->getTableName('catalog_category_product');
        $tableCurpc = $connection->getTableName('catalog_url_rewrite_product_category');
        $tableCpip = $connection->getTableName('catalog_product_index_price');
        $this->addStoreFilter($storeId);
        if (!$includeDisabled) {
            $this->addAttributeToFilter('status', '1');
        }
        if (!in_array("*", explode(',', (string) $params['type_ids']))) {
            $this->addAttributeToFilter('type_id', ['in' => explode(',', (string) $params['type_ids'])]);
        }
        if (!in_array("*", explode(',', (string) $params['visibilities']))) {
            $this->addAttributeToFilter('visibility', ['in' => explode(',', (string) $params['visibilities'])]);
        }
        if (!in_array("*", explode(",", (string) $params['attribute_sets']))) {
            $this->addAttributeToFilter('attribute_set_id', ['in' => explode(',', (string) $params['attribute_sets'])]);
        }
        $joinType = count($listOfAttributes) <= self::MAX_ATTRIBUTE;
        $this->addAttributeToSelect($listOfAttributes, $joinType);
        $where = '';
        $a = 0;
        $tempFilter = [];
        if ($manageStock != 1 && $manageStock != 0) {
            throw new \Exception(__('Invalid data'));
        } else {
            $manageStock = htmlspecialchars($manageStock);
        }
        foreach (json_decode((string) $params['attributes']) as $attributeFilter) {
            if ($attributeFilter->checked) {
                if ($attributeFilter->condition == 'in' || $attributeFilter->condition == 'nin') {
                    if ($attributeFilter->code == 'qty' || $attributeFilter->code == 'is_in_stock') {
                        if (!is_array($attributeFilter->value)) {
                            $array = explode(',', (string) $attributeFilter->value);
                        } else {
                            $array = $attributeFilter;
                        }
                        $attributeFilter->value = "'" . implode("','", $array) . "'";
                    } else {
                        if (!is_array($attributeFilter->value)) {
                            $attributeFilter->value = explode(',', (string) $attributeFilter->value);
                        }
                    }
                }
                if (!isset($attributeFilter->statement)) {
                    $attributeFilter->statement = "";
                }
                switch ($attributeFilter->code) {
                    case 'qty':
                        if ($a > 0) {
                            $where .= ' ' . $attributeFilter->statement . ' ';
                        }
                        $where .= ' qty ' . sprintf($condition[$attributeFilter->condition], $attributeFilter->value);
                        $a++;
                        break;
                    case 'is_in_stock':
                        if ($a > 0) {
                            $where .= ' ' . $attributeFilter->statement . ' ';
                        }
                        $where .= "(IF(";
                        // use_config_manage_stock=1 && default_manage_stock=0
                        $where .= "(use_config_manage_stock=1 AND {$manageStock}=0)";
                        // use_config_manage_stock=0 && manage_stock=0
                        $where .= " OR ";
                        $where .= "(use_config_manage_stock=0 AND manage_stock=0)";
                        // use_config_manage_stock=1 && default_manage_stock=1 && in_stock=1
                        $where .= " OR ";
                        $where .= "(use_config_manage_stock=1 AND {$manageStock}=1 AND is_in_stock=1 )";
                        // use_config_manage_stock=0 && manage_stock=1 && in_stock=1
                        $where .= " OR ";
                        $where .= "(use_config_manage_stock=0 AND manage_stock=1 AND is_in_stock=1 )";
                        $where .= ",'1','0')" . sprintf($condition[$attributeFilter->condition], $attributeFilter->value) . ")";
                        $a++;
                        break;
                    default:
                        if ($attributeFilter->statement == 'AND') {
                            if (count($tempFilter)) {
                                $this->addFieldToFilter($tempFilter);
                            }
                            $tempFilter = [];
                        }
                        if ($attributeFilter->condition == 'in') {
                            $finset = true;
                            $findInSet = [];
                            foreach ($attributeFilter->value as $v) {
                                if (!is_numeric($v)) {
                                    $finset = true;
                                }
                            }
                            if ($finset) {
                                foreach ($attributeFilter->value as $v) {
                                    $findInSet[] = [['finset' => $v]];
                                }
                                $tempFilter[] = ['attribute' => $attributeFilter->code, $findInSet];
                            } else {
                                $tempFilter[] = ['attribute' => $attributeFilter->code, $attributeFilter->condition => $attributeFilter->value];
                            }
                        } else {
                            $tempFilter[] = ['attribute' => $attributeFilter->code, $attributeFilter->condition => $attributeFilter->value];
                        }
                        break;
                }
            }
        }
        if (count($tempFilter)) {
            $this->addFieldToFilter($tempFilter);
        }
        $this->getSelect()->joinLeft(['stock' => $tableCsi], "stock.product_id=e.entity_id", ["qty" => "qty", "is_in_stock" => "is_in_stock", "manage_stock" => "manage_stock", "use_config_manage_stock" => "use_config_manage_stock", "backorders" => "backorders", "use_config_backorders" => "use_config_backorders"]);
        $this->getSelect()->joinLeft(['url' => $tableCur], "url.entity_id=e.entity_id " . $notLike . " AND url.entity_type ='product' AND url.store_id=" . $storeId, ["request_path" => $concat . "(DISTINCT request_path)"]);
        $this->getSelect()->joinLeft(["curpc" => $tableCurpc], "url.url_rewrite_id=curpc.url_rewrite_id and e.entity_id = curpc.product_id");
        if ($categoriesFilterList[0] != "*") {
            $v = 0;
            $filter = null;
            foreach ($categoriesFilterList as $categoriesFilter) {
                if ($v > 0) {
                    $filter .= ',';
                }
                $explode = explode("/", (string) $categoriesFilter);
                $filter .= array_pop($explode);
                $v++;
            }
            $in = $params['category_filter'] ? 'IN' : 'NOT IN';
            $ct = '';
            switch ($params['category_type']) {
                case self::CATEGORIES_FILTER_PRODUCT:
                    $ct = 'categories.product_id=e.entity_id';
                    break;
                case self::CATEGORIES_FILTER_PRODUCT_AND_PARENT:
                    $this->getSelect()->joinLeft(['cpsl' => $tableCpsl], "cpsl.product_id=e.entity_id", ['parent_id' => 'parent_id']);
                    $this->getSelect()->joinLeft(['cpslcpe' => $tableCpe], "cpsl.parent_id=cpslcpe." . $this->_rowId . "", []);
                    $ct = "(categories.product_id=e.entity_id OR categories.product_id=cpslcpe.entity_id)";
                    break;
                case self::CATEGORIES_FILTER_PARENT:
                    $this->getSelect()->joinLeft(['cpsl' => $tableCpsl], "cpsl.product_id=e.entity_id", ['parent_id' => 'parent_id']);
                    $this->getSelect()->joinLeft(['cpslcpe' => $tableCpe], "cpsl.parent_id=cpslcpe." . $this->_rowId . "", []);
                    $ct = "categories.product_id=cpslcpe.entity_id ";
                    break;
            }
            $filter = " AND categories.category_id " . $in . "(" . $filter . ") ";
            $this->getSelect()->joinInner(['categories' => $tableCcpi], $ct . $filter, ["categories_ids" => "GROUP_CONCAT( DISTINCT categories.category_id)"]);
            $this->getSelect()->joinInner(['cce' => $tableCce], "categories.category_id=cce.entity_id AND cce.path LIKE '" . $categoryRootId . "/" . $storeRootId . "/%'", []);
        } else {
            $this->getSelect()->joinLeft(['categories' => $tableCcpi], "categories.product_id=e.entity_id", ["categories_ids" => "GROUP_CONCAT( DISTINCT categories.category_id)"]);
            $this->getSelect()->joinLeft(['cce' => $tableCce], "categories.category_id=cce.entity_id AND cce.path LIKE '" . $categoryRootId . "/" . $storeRootId . "/%'", []);
        }
        $this->getSelect()->joinLeft(['price_index' => $tableCpip], "price_index.entity_id=e.entity_id AND customer_group_id=0 AND  price_index.website_id=" . $websiteId, ['min_price' => 'min_price', 'max_price' => 'max_price', 'final_price' => 'final_price', 'base_price' => 'price']);
        if (!empty($where)) {
            $this->getSelect()->where($where);
        }
        $this->getSelect()->group('e.entity_id');
        return $this;
    }
}