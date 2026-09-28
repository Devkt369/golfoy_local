<?php
namespace Mango\Ajaxlayerednavigation\Model\Layer\Filter;

//use Magento\Catalog\Model\Layer\Filter\AbstractFilter;
use Magento\Catalog\Model\Layer\Filter\DataProvider\Category as CategoryDataProvider;

/**
 * Layer category filter
 */
class Category extends \Magento\CatalogSearch\Model\Layer\Filter\Category
{
    /**
     * @var \Magento\Framework\Escaper
     */
    private $escaper;
    /**
     * @var CategoryDataProvider
     */
    private $dataProvider;
    protected $isFilterActive;
    protected $categoryCollectionFactory;
    protected $filtersCount;
    protected $helper;
    
    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Framework\Escaper $escaper
     * @param CategoryManagerFactory $categoryManager
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Framework\Escaper $escaper,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\CategoryFactory $categoryDataProviderFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $escaper,
            $categoryDataProviderFactory,
            $data
        );
        $this->helper = $helper;
        $this->escaper = $escaper;
        $this->requestVar = 'cat';
        $this->dataProvider = $categoryDataProviderFactory->create(['layer' => $this->getLayer()]);
        $this->categoryCollectionFactory = $categoryCollectionFactory;
    }
    /**
     * Apply category filter to product collection
     *
     * @param   \Magento\Framework\App\RequestInterface $request
     * @return  $this
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return parent::apply($request);
        }
        $filter = $request->getParam($this->requestVar) ? : $request->getParam('id');
        if (empty($filter)) {
            return $this;
        }
        if (preg_match('/^[0-9,]+$/', $filter)) {
            $filter = array_unique(explode(',', $filter));
        }
        if (!is_array($filter) || empty($filter)) {
            return $this;
        }
        /* add multiple categories as filter... */
        $productCollection = $this->getLayer()->getProductCollection();
        $productCollection->addFieldToFilter('category_ids', $filter);
        
        /* get category collection based on selected filters */
        $categories = $this->categoryCollectionFactory->create();
        $categories->addNameToResult()->addFieldToFilter('entity_id', $filter);
        $categoriesArray = [];
        foreach ($categories as $catInfo) {
            $categoriesArray[$catInfo->getId()] = $catInfo;
        }
        
        foreach ($filter as $cat_filter) {
            if ($request->getParam('id') == $cat_filter) {
                continue;
            }
            $cat = isset($categoriesArray[$cat_filter]) ? $categoriesArray[$cat_filter]:false;
            if ($cat) {
                $this->getLayer()->getState()->addFilter($this->_createItem($cat->getName(), $cat->getId()));
            }
            $this->isFilterActive = true;
        }
        return $this;
    }
    
    /**
     * Get data array for building category filter items
     *
     * @return array
     */
    protected function _getItemsData()
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return parent::_getItemsData();
        }
        $category = $this->dataProvider->getCategory();
        $categories = $category->getChildrenCategories();
        /* todo: return just one filter, for filling the block, the rest is handled by the tree block */
        if ($category->getIsActive()) {
            foreach ($categories as $category) {
                if ($category->getIsActive()
                ) {
                    $this->itemDataBuilder->addItemData(
                        $this->escaper->escapeHtml($category->getName()),
                        $category->getId(),
                        1
                    );
                }
            }
        }
        return $this->itemDataBuilder->build();
    }
    public function getFiltersCount()
    {
        if (!$this->filtersCount) {
            $productCollection = $this->getLayer()->getProductCollection();
            $this->filtersCount = $productCollection->getFacetedData('category');
        }
        return $this->filtersCount;
    }
    public function isFilterActive()
    {
        return $this->isFilterActive;
    }
    public function getCode()
    {
        return $this->requestVar;
    }
    /*  tasks
      1. get product count per category item, based on the applied filters
     *  2. apply filter to collection based on the selected id's
     *  3. on both cases, uses the table catalog_category_product_index to get the list of selected items
     *      */
}
