<?php

namespace ArtM\Common\Block\Product;

use Magento\Catalog\Model\Product;
use Magento\Framework\Pricing\Helper\Data;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;

class ListProduct extends \Magento\Catalog\Block\Product\ListProduct
{
    protected $_customerSession;
    protected $categoryFactory;
    protected $priceHelperData;
    protected $productCacheRepository;
    protected $productCacheFactory;
    protected $artmSortingProducts;
    private CollectionFactory $attributeSetCollectionFactory;
    private ?array $attributeSetMap = null;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * ListProduct constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Data\Helper\PostHelper $postDataHelper
     * @param \Magento\Catalog\Model\Layer\Resolver $layerResolver
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Magento\Framework\Url\Helper\Data $urlHelper
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        CollectionFactory $attributeSetCollectionFactory,
        ProductRepositoryInterface $productRepository,
        ScopeConfigInterface $scopeConfig,
        array $data = [],
    ) {
        $this->_customerSession = $customerSession;
        $this->categoryFactory = $categoryFactory;
        $this->productRepository = $productRepository;
        $this->_scopeConfig = $scopeConfig;
        $this->attributeSetCollectionFactory = $attributeSetCollectionFactory;
        parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    public function getPointData($productId){
        $artmSortingProductModel = $this->artmSortingProducts->create();
        $item = $artmSortingProductModel->getCollection()
            ->addFieldToFilter('product_id', $productId)
            ->addFieldToSelect([
                'updated_at',
                'high_discount_points',
                'availability_other',
                'availability_cgc',
                'availability_golfoy',
                'is_feature',
                'total_points',
                'size_roll_points',
                'created_date_points'
            ])
            ->getFirstItem();
        return $item->getData();
    }

    public function getAttributeSetMap(): array
    {
        if ($this->attributeSetMap !== null) {
            return $this->attributeSetMap;
        }

        $collection = $this->attributeSetCollectionFactory->create()
            ->setEntityTypeFilter(Product::ENTITY);

        $map = [];
        foreach ($collection as $set) {
            $map[(int)$set->getId()] = $set->getAttributeSetName();
        }

        return $this->attributeSetMap = $map;
    }

    public function getCustomOptionPrice($productId)
    {
        try {
            $product = $this->productRepository->getById($productId);
            $options = $product->getOptions();
            if (empty($options)) {
                return '';
            }
            $prices = [];
            foreach ($options as $option) {
                $prices[] = (float)$option['price'];
            }
            
            $zeroCount = count(array_filter($prices, function ($price) {
                return $price == 0.0;
            }));
            
            if ($zeroCount === count($prices)) {
                return 'free';
            } elseif ($zeroCount > 0) {
                return 'partial_free';
            } else {
                return 'not_free';
            }
        } catch (NoSuchEntityException $e) {
            return '';
        }
    }
}