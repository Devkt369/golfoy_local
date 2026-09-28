<?php
namespace ArtM\Customcache\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ResourceConnection;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;

class TopBlock extends Template
{
    protected $_resource;
    protected $_categoryFactory;
    protected $_productCollectionFactory;

    public function __construct(
        Template\Context $context,
        ResourceConnection $resource,
        CategoryFactory $categoryFactory,
        ProductCollectionFactory $productCollectionFactory,
        array $data = []
    ) {
        $this->_resource = $resource;
        $this->_categoryFactory = $categoryFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        parent::__construct($context, $data);
    }

    public function getSubcategoryIconsHtml($catId)
    {
        $catId = (int) $catId;
        $cache = $this->getCache($catId);
        if ($cache) {
            return $cache;
        }

        $category = $this->_categoryFactory->create()->load($catId);
        if (!$category || !$category->getId()) {
            return '';
        }

        $subcats = $category->getChildrenCategories();
        $icons = [];
        // Only use the first 1st-level subcategory
        $firstSubcat = null;
        foreach ($subcats as $subcat) {
            if ($subcat->getIsActive()) {
                $firstSubcat = $subcat;
                break;
            }
        }
        if ($firstSubcat) {
            $children = $firstSubcat->getChildrenCategories();
            foreach ($children as $child) {
                if ($child->getIsActive() && $this->isCategoryIncludedInMenu($child)) {
                    $icons[] = [
                        'name' => $child->getName(),
                        'image' => $this->getAnyProductImage($child->getId()),
                        'url' => $child->getUrl()
                    ];
                }
            }
        }

        $html = $this->renderIconsHtml($icons);
        if (trim((string) $html) !== '') {
            $this->saveCache($catId, $html);
        }
        return $html;
    }

    protected function isCategoryIncludedInMenu($category)
    {
        $includeInMenu = $category->getData('include_in_menu');

        // Children collections may not always preload include_in_menu.
        if ($includeInMenu === null && $category->getId()) {
            $fullCategory = $this->_categoryFactory->create()->load((int) $category->getId());
            $includeInMenu = $fullCategory->getData('include_in_menu');
        }

        return (int) $includeInMenu === 1;
    }

    protected function getAnyProductImage($categoryId)
    {
        $collection = $this->_productCollectionFactory->create();
        $collection->addCategoriesFilter(['in' => $categoryId]);
        $collection->addAttributeToSelect('small_image');
        $collection->addAttributeToSelect('name');
        $collection->setPageSize(1);
        $collection->setCurPage(1);
        $product = $collection->getFirstItem();
        if ($product && $product->getId()) {
            return $this->getImageUrl($product);
        }
        return '';
    }

    protected function getImageUrl($product)
    {
        return $this->getUrl('pub/media/catalog/product') . $product->getSmallImage();
    }

    protected function renderIconsHtml($icons)
    {
        return $this->getLayout()->createBlock(\Magento\Framework\View\Element\Template::class)
            ->setTemplate('ArtM_Customcache::top_block.phtml')
            ->setData('icons', $icons)
            ->toHtml();
    }

    protected function getCache($catId)
    {
        $connection = $this->_resource->getConnection();
        $select = $connection->select()
            ->from('artm_custom_cache', ['content'])
            ->where('type = ?', 'top_block')
            ->where('page_type = ?', 'category_page')
            ->where('cat_id = ?', $catId)
            ->order('created_at DESC')
            ->limit(1);
        $result = $connection->fetchOne($select);
        return $result;
    }

    protected function saveCache($catId, $html)
    {
        $connection = $this->_resource->getConnection();
        $connection->insert('artm_custom_cache', [
            'type' => 'top_block',
            'page_type' => 'category_page',
            'cat_id' => $catId,
            'content' => $html,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function toHtml()
    {
        $catId = $this->getData('cat_id');
        return $this->getSubcategoryIconsHtml($catId);
    }
}
