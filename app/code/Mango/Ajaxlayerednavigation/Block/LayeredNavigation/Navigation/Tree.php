<?php
/**
 * Categories tree block
 */
namespace Mango\Ajaxlayerednavigation\Block\LayeredNavigation\Navigation;

use Magento\Framework\Registry;

class Tree
{
    protected $categoryTree;
    private $registry;
    protected $categoryId;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;
    protected $storeManager;
    protected $categoryCollection = null;
    private $active_filter;
    #protected $request;
    protected $escaper;
    /* taken from calog/layer/filter/item */
    protected $htmlPagerBlock;
    protected $url;
    /* assign filter via plugin */
    protected $filter;
    protected $filtersCount;
    /* flat category check */
    protected $flatCategory;
    protected $flatCategoryState;
    protected $flatCategoryStateChecked = false;
    /* extension helper */
    protected $helper;
    
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        Registry $registry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        #\Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Escaper $escaper,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Magento\Framework\UrlInterface $url,
        \Magento\Catalog\Model\Indexer\Category\Flat\State $flatCategory,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper
    ) {
        $this->categoryTree = $categoryTree;
        $this->categoryFactory = $categoryFactory;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        #$this->request = $request;
        $this->escaper = $escaper;
        $this->htmlPagerBlock = $htmlPagerBlock;
        $this->url = $url;
        $this->flatCategory = $flatCategory;
        $this->helper = $helper;
    }
    public function showItemsNoCount()
    {
        return $this->helper->getConfigValue("show_no_count", "general");
    }
    
    
    public function setFilter($filter)
    {
        $this->filter = $filter;
        /* function from category class... */
        $this->filtersCount = $this->filter->getFiltersCount();
        return $this;
    }
    
    public function getCategoryId()
    {
        if (!$this->categoryId) {
            $cat = $this->registry->registry('current_category');
            if ($cat) {
                $this->categoryId = $cat->getId();
            } else { /* probably in search results page */
                $this->categoryId = $this->storeManager->getStore()->getRootCategoryId();
            }
        }
        return $this->categoryId;
    }
    public function getCategoriesTree()
    {
        $depth = 5;
        $tree = $this->getTree($this->getCategoryId(), $depth);
        
        $include_parent = $this->helper->getConfigValue("category_tree_parent", "general");
        
        $html = $this->_getNodeHtml($tree, $depth, 0, false, false, $include_parent);
        return $html;
    }
    /**
     * @param mixed|null $parenNodeCategory
     * @return array
     */
    public function getTree($parentNodeCategoryId, $recursionLevel = 5)
    {
        if ($parentNodeCategoryId) {
            return $this->getNode($parentNodeCategoryId, $recursionLevel);
        }
        return [];
    }
    /**
     * @param mixed $parentNodeCategory
     * @param int $recursionLevel
     * @return Node
     */
    public function getNode($parentNodeCategoryId, $recursionLevel = 5)
    {
        //$nodeId = $parentNodeCategory->getId();
        $node = $this->categoryTree->loadNode($parentNodeCategoryId);
        $node->loadChildren($recursionLevel);
        $this->categoryTree->addCollectionData($this->getCategoryCollection());
        return $node;
    }
    
    public function isFlatCategoryState()
    {
        if (!$this->flatCategoryStateChecked) {
            $this->flatCategoryState = $this->flatCategory->isFlatEnabled();
            $this->flatCategoryStateChecked = true;
        }
        return $this->flatCategoryState;
    }
    
    /**
     * @return \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
     */
    public function getCategoryCollection()
    {
        $storeId = $this->storeManager->getStore()->getId();
        if ($this->categoryCollection === null) {
            $this->categoryCollection = $this->categoryFactory->create()->getCollection();
            $this->categoryCollection->addAttributeToSelect(
                'name'
            )->addAttributeToSelect(
                'is_active'
            )->addAttributeToSelect(
                'include_in_menu'
            )->setStoreId(
                $storeId
            );
            if (!$this->isFlatCategoryState()) {
                /*$this->categoryCollection->setStoreId(
                        $storeId
                );*/
                /*} else {*/
                $this->categoryCollection->setProductStoreId(
                    $storeId
                )->setLoadProductCount(
                    false /* will get from layered navigation model - faceted data */
                );
            }
        }
        return $this->categoryCollection;
    }
    /**
     * Template for filter items block
     */
    /* first: get category tree based on current category... */
    public function _getNodeHtml(
        $category,
        $parentCategoryId = 0,
        $level = 0,
        $isLast = false,
        $isFirst = false,
        $include_parent = false,
        $return_child_ids = false
    ) {
    
        if (is_array($category)) {
            $category = new Node($category, 'entity_id', new \Magento\Framework\Data\Tree());
        }
        if (!$category->getIsActive()) {
            return '';
        }
        $html = [];
        /* find children only if the category depth is valid... */
        $children = ( $category->hasChildren() ) ? $category->getChildren() : [];
        // select active children
        $activeChildren = [];
        foreach ($children as $child) {
            if ($child->getIsActive()) {
                $activeChildren[] = $child;
            }
        }
        $activeChildrenCount = count($activeChildren);
        $hasActiveChildren = ($activeChildrenCount > 0);
        // prepare list item html classes
        $classes = [];
        $classes[] = 'level' . $level;
        $linkClass = '';
        $selectAllClass = [];
        if ($isFirst) {
            $classes[] = 'first';
        }
        if ($isLast) {
            $classes[] = 'last';
        }
        if ($hasActiveChildren) {
            $classes[] = 'parent';
            $linkClass = "category-expand link-expand-collapse";
        } else {
            $classes[] = 'child';
            $linkClass = "category-filter-link";
        }
        if ($this->isItemActive($category->getId())) {
            $classes[] = 'active-filter-option';
        }
        // prepare list item attributes
        $attributes = [];
        if (count($classes) > 0) {
            $attributes['class'] = implode(' ', $classes);
        }
        
        $count = isset($this->filtersCount[$category->getId()]["count"]) ? $this->filtersCount[$category->getId()]["count"] : 0;
        $show_item = $count>0 || ($this->showItemsNoCount() && $count == 0);
        if ($include_parent && $show_item) {
            // assemble list item with attributes
            $htmlLi = '<li';
            foreach ($attributes as $attrName => $attrValue) {
                $htmlLi .= ' ' . $attrName . '="' . str_replace('"', '\"', $attrValue) . '"';
            }
            $htmlLi .= '>';
            $html[] = $htmlLi;
            $html[] = '<a href="' . $this->getUrl([$category->getId()], $parentCategoryId) . '" data-attribute-value="' . $category->getId() . '" class="' . $linkClass . '">';
            $html[] = $this->escaper->escapeHtml($category->getName()); //Mage::helper('core')->escapeHtml($category->getName());
            
            $html[] = '<span class="item-count">(' . (int) $count . ')</span>';
            $html[] = '</a>';
        }
        // render children
        $htmlChildren = '';
        $j = 0;
        $children_ids = [$category->getId()];
        foreach ($activeChildren as $child) {
            $children_ids[] = $child->getId();
            $processChildren = $this->_getNodeHtml(
                $child,
                $category->getId(),
                ($level + 1),
                ($j == $activeChildrenCount - 1),
                ($j == 0),
                true,
                true
            );
            $j++;
            $htmlChildren .= $processChildren[0]; /* index 0 : html , index 1: array of children id's */
            $children_ids = array_merge($children_ids, $processChildren[1]);
        }
        if (!empty($htmlChildren)) {
            $children_ids = array_unique($children_ids);
            if ($hasActiveChildren && $include_parent) {
                if ($this->isParentItemActive($children_ids)) {
                    $selectAllClass[] = 'all-selected';
                }
                $html[] = '<a href="' . $this->getUrl($children_ids, $category->getId()) . '" data-attribute-value="' . implode(",", $children_ids) . '" class="select-all-subcategories-link category-filter-link' . implode(" ", $selectAllClass) . '">'
                        . '<span class="select-all-subcategories-text">' . $this->escaper->escapeHtml($category->getName()) . '</span>'
                        . '</a>';
            }
            if ($include_parent) {
                $html[] = '<ul class="level' . $level . '">';
            }
            $html[] = $htmlChildren;
            if ($include_parent) {
                $html[] = '</ul>';
            }
        }
        if ($include_parent && $show_item) {
            $html[] = '</li>';
        }
        $html = implode("\n", $html);
        if ($return_child_ids) {
            return [$html, $children_ids];
        }
        /* default return... */
        return $html;
    }
    public function getUrl($values, $parent_id = null)
    {
        $filter = $this->getActiveFilters();
        if (count($values) === 1) { /* link to remove current filter only and its parent */
            foreach ($values as $value) {
                if (in_array($value, $filter)) {/* item is selected, then remove from array */
                    array_splice($filter, array_search($value, $filter), 1);
                    if (in_array($parent_id, $filter)) { /* also remove its parent, to be selected, all child items should be selected */
                        array_splice($filter, array_search($parent_id, $filter), 1);
                    }
                } else {
                    $filter[] = $value;
                }
            }
        } else {
            /* item is a parent link and parameter should include its children
             * if all items are selected, then create link to remove all values (uncheck) ,
             * otherwise, create link to select all values ( check all )  */
            # checking if all items are selected
            $all_items_selected = true;
            foreach ($values as $value) {
                if (!in_array($value, $filter)) {
                    $all_items_selected = false;
                    break;
                }
            }
            if ($all_items_selected) {
                $filter_2 = []; /* the url parameter includes the parent category id */
                foreach ($filter as $v) {
                    /* skip filter items */
                    if (!in_array($v, $values) && $v != $parent_id) {
                        $filter_2[] = $v;
                    }
                }
                $filter = $filter_2;
            } else {
                /* remove parent id from filters array */
                if ($parent_id && array_search($parent_id, $filter) !== false) {
                    array_splice($filter, array_search($parent_id, $filter), 1);
                }
                /*  */
                foreach ($values as $value) {
                    if (!in_array($value, $filter)) {
                        $filter[] = $value;
                    }
                }
            }
        }
        $filter = join(",", array_unique($filter));
        if (!trim($filter)) {
            $filter = null;
        }
        $query = [
            'cat' => $filter,
            // exclude current page from urls
            $this->htmlPagerBlock->getPageVarName() => null,
            // exclude ajax parameters
            'ajax_request' => null,
            'ajaxlist' => null,
            'reload_navigation' => null,
            '_' => null
        ];
        $url = $this->url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
        return $url;
    }
    public function isItemActive($value)
    {
        /* todo: get it from model */
        $filter = $this->getActiveFilters();
        if (in_array($value, $filter)) {
            return true;
        } else {
            return false;
        }
    }
    public function getActiveFilters()
    {
        if ($this->active_filter == false) {
            /* active filters based on parameters
             * todo: get it from model... */
            $url_param = $this->helper->getRequest()->getParam('cat');
            $filter = [];
            if (preg_match('/^[0-9,]+$/', (string) $url_param)) {
                $filter = explode(',', $url_param);
            } elseif ((int) $url_param > 0) {
                $filter[] = $url_param;
            }
            $this->active_filter = $filter;
        }
        return $this->active_filter;
    }
    public function isParentItemActive($values)
    {
        /* todo: get it from model */
        $filter = $this->getActiveFilters();
        foreach ($values as $value) {
            if (!in_array($value, $filter)) {
                return false;
            }
        }
        return true;
    }
}
