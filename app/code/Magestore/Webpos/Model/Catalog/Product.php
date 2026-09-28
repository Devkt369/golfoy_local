<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Catalog;

use Exception;
use Magento\Bundle\Model\Option;
use Magento\Bundle\Model\Product\Price;
use Magento\Bundle\Model\Product\Type;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductLinkExtensionFactory;
use Magento\Catalog\Api\Data\ProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Catalog\Model\Indexer\Product\Eav\Processor;
use Magento\Catalog\Model\Product\Attribute\Backend\Media\EntryConverterPool;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Image\CacheFactory;
use Magento\Catalog\Model\Product\Link;
use Magento\Catalog\Model\Product\LinkTypeProvider;
use Magento\Catalog\Model\Product\Media\Config;
use Magento\Catalog\Model\Product\OptionFactory;
use Magento\Catalog\Model\Product\Url;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductLink\CollectionProvider;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem;
use Magento\Framework\Indexer\IndexerRegistry;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Locale\FormatInterface;
use Magento\Framework\Model\Context;
use Magento\Framework\Module\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Weee\Model\Tax;
use Magestore\Giftvoucher\Model\GiftTemplate;
use Magestore\Giftvoucher\Model\Product\Type\Giftvoucher;
use Magestore\Giftvoucher\Model\Source\GiftCardTypeOptions;
use Magestore\Webpos\Api\Catalog\ProductOptionsRepositoryInterface;
use Magestore\Webpos\Api\Catalog\ProductSearchRepositoryInterface;
use Magestore\Webpos\Api\Data\Catalog\Option\GiftCardPriceOptionsInterface;
use Magestore\Webpos\Api\Data\Catalog\Option\GiftCardPriceOptionsInterfaceFactory;
use Magestore\Webpos\Api\Data\Catalog\Option\GiftCardTemplateInterfaceFactory;
use Magestore\Webpos\Api\Data\Catalog\Option\ProductOptionsInterface;
use Magestore\Webpos\Api\Data\Catalog\ProductInterface;
use Magestore\Webpos\Api\Data\Catalog\ProductOriginalInterface;
use Magestore\Webpos\Api\Data\CatalogRule\Product\PriceInterface;
use Magestore\Webpos\Api\Data\Inventory\StockItemInterface;
use Magestore\Webpos\Api\Inventory\StockItemRepositoryInterface;
use Magestore\Webpos\Api\Location\LocationRepositoryInterface;
use Magestore\Webpos\Api\MultiSourceInventory\StockManagementInterface;
use Magestore\Webpos\Api\WebposManagementInterface;
use Magestore\Webpos\Helper\Data;
use Magestore\Webpos\Helper\Profiler;
use Magestore\Webpos\Model\ResourceModel\CatalogRule\Product\Price\Collection;
use Magestore\Webpos\Model\ResourceModel\CatalogRule\Product\Price\CollectionFactory as PriceCollectionFactory;
use Magento\InventoryConfigurationApi\Api\GetStockItemConfigurationInterface;
use Magento\InventorySalesAdminUi\Model\ResourceModel\GetAssignedStockIdsBySku;
use Magento\InventorySalesApi\Api\GetProductSalableQtyInterface;

/**
 * Catalog product model
 *
 * @method Product setHasError(bool $value)
 * @method \Magento\Catalog\Model\ResourceModel\Product getResource()
 * @method null|bool getHasError()
 * @method Product setAssociatedProductIds(array $productIds)
 * @method array getAssociatedProductIds()
 * @method Product setNewVariationsAttributeSetId(int $value)
 * @method int getNewVariationsAttributeSetId()
 * @method \Magento\Catalog\Model\ResourceModel\Product\Collection getCollection()
 * @method string getUrlKey()
 * @method Product setUrlKey(string $urlKey)
 * @method Product setRequestPath(string $requestPath)
 * @method Product setWebsiteIds(array $ids)
 *
 * @SuppressWarnings(PHPMD.LongVariable)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Product extends \Magento\Catalog\Model\Product implements ProductInterface
{
    /**
     * @var Configurable
     */
    protected $_configurableProduct;

    /**
     * @var \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
     */
    protected $_configurableProductBlock;

    /**
     * @var Image
     */
    protected $_helperImage;

    /**
     * @var Emulation
     */
    protected $_emulation;

    /**
     * @var FormatInterface
     */
    protected $_formatInterface;

    /**
     * @var PriceCurrencyInterface
     */
    protected $_priceCurrencyInterface;

    /**
     * @var StockItemRepositoryInterface
     */
    protected $_stockItemRepositoryInterFace;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var CollectionFactory
     */
    protected $_categoryCollectionFactory;

    /**
     * @var Data
     */
    protected $_webposHelper;

    /**
     * @var ProductFactory
     */
    protected $_productFactory;

    /**
     * @var Grouped
     */
    protected $_groupedProductType;

    /**
     * @var StockRegistryInterface
     */
    protected $_stockRegistryInterface;

    /**
     * @var Option
     */
    protected $_bundleOption;

    /**
     * @var Type
     */

    protected $_bundleProductType;
    /**
     * @var ProductOptionsRepositoryInterface
     */
    protected $productOptionsRepository;

    /**
     * @var
     */
    protected $_productOptions;

    /**
     * @var Tax
     */
    protected $weeTax;

    /**
     * @var ProductSearchRepositoryInterface
     */
    protected $productSearchRepository;

    /**
     * @var PriceCollectionFactory
     */
    protected $catalogRuleProductPricecollectionFactory;

    /**
     * @var GiftCardPriceOptionsInterfaceFactory
     */
    protected $giftCardPriceOptionsInterfaceFactory;

    /**
     * @var GiftCardTemplateInterfaceFactory
     */
    protected $giftCardTemplateInterfaceFactory;

    /**
     * @var WebposManagementInterface
     */
    protected $webposManagement;

    /**
     * @var StockManagementInterface
     */
    protected $stockManagement;

    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Product constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $customAttributeFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductAttributeRepositoryInterface $metadataService
     * @param Url $url
     * @param Link $productLink
     * @param \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param OptionFactory $catalogProductOptionFactory
     * @param Visibility $catalogProductVisibility
     * @param Status $catalogProductStatus
     * @param Config $catalogProductMediaConfig
     * @param \Magento\Catalog\Model\Product\Type $catalogProductType
     * @param Manager $moduleManager
     * @param \Magento\Catalog\Helper\Product $catalogProduct
     * @param \Magento\Catalog\Model\ResourceModel\Product $resource
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $resourceCollection
     * @param \Magento\Framework\Data\CollectionFactory $collectionFactory
     * @param Filesystem $filesystem
     * @param IndexerRegistry $indexerRegistry
     * @param \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor
     * @param \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor
     * @param Processor $productEavIndexerProcessor
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CacheFactory $imageCacheFactory
     * @param CollectionProvider $entityCollectionProvider
     * @param LinkTypeProvider $linkTypeProvider
     * @param ProductLinkInterfaceFactory $productLinkFactory
     * @param ProductLinkExtensionFactory $productLinkExtensionFactory
     * @param EntryConverterPool $mediaGalleryEntryConverterPool
     * @param DataObjectHelper $dataObjectHelper
     * @param JoinProcessorInterface $joinProcessor
     * @param Configurable $configurableProduct
     * @param \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $configurableProductBlock
     * @param Image $helperImage
     * @param Emulation $emulation
     * @param FormatInterface $formatInterface
     * @param PriceCurrencyInterface $priceCurrencyInterface
     * @param StockItemRepositoryInterface $stockItemRepositoryInterFace
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param CollectionFactory $categoryCollectionFactory
     * @param Data $webposHelper
     * @param ProductFactory $productFactory
     * @param Grouped $groupedProductType
     * @param StockRegistryInterface $stockRegistryInterface
     * @param Option $bundleOption
     * @param Type $bundleProductType
     * @param ProductOptionsRepositoryInterface $productOptionsRepository
     * @param Tax $weeTax
     * @param ProductSearchRepositoryInterface $productSearchRepository
     * @param PriceCollectionFactory $catalogRuleProductPricecollectionFactory
     * @param GiftCardPriceOptionsInterfaceFactory $giftCardPriceOptionsInterfaceFactory
     * @param GiftCardTemplateInterfaceFactory $giftCardTemplateInterfaceFactory
     * @param WebposManagementInterface $webposManagement
     * @param StockManagementInterface $stockManagement
     * @param ObjectManagerInterface $objectManager
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        StoreManagerInterface $storeManager,
        ProductAttributeRepositoryInterface $metadataService,
        Url $url,
        Link $productLink,
        \Magento\Catalog\Model\Product\Configuration\Item\OptionFactory $itemOptionFactory,
        StockItemInterfaceFactory $stockItemFactory,
        OptionFactory $catalogProductOptionFactory,
        Visibility $catalogProductVisibility,
        Status $catalogProductStatus,
        Config $catalogProductMediaConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        Manager $moduleManager,
        \Magento\Catalog\Helper\Product $catalogProduct,
        \Magento\Catalog\Model\ResourceModel\Product $resource,
        \Magento\Catalog\Model\ResourceModel\Product\Collection $resourceCollection,
        \Magento\Framework\Data\CollectionFactory $collectionFactory,
        Filesystem $filesystem,
        IndexerRegistry $indexerRegistry,
        \Magento\Catalog\Model\Indexer\Product\Flat\Processor $productFlatIndexerProcessor,
        \Magento\Catalog\Model\Indexer\Product\Price\Processor $productPriceIndexerProcessor,
        Processor $productEavIndexerProcessor,
        CategoryRepositoryInterface $categoryRepository,
        CacheFactory $imageCacheFactory,
        CollectionProvider $entityCollectionProvider,
        LinkTypeProvider $linkTypeProvider,
        ProductLinkInterfaceFactory $productLinkFactory,
        ProductLinkExtensionFactory $productLinkExtensionFactory,
        EntryConverterPool $mediaGalleryEntryConverterPool,
        DataObjectHelper $dataObjectHelper,
        JoinProcessorInterface $joinProcessor,
        Configurable $configurableProduct,
        \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable $configurableProductBlock,
        Image $helperImage,
        Emulation $emulation,
        FormatInterface $formatInterface,
        PriceCurrencyInterface $priceCurrencyInterface,
        StockItemRepositoryInterface $stockItemRepositoryInterFace,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CollectionFactory $categoryCollectionFactory,
        Data $webposHelper,
        ProductFactory $productFactory,
        Grouped $groupedProductType,
        StockRegistryInterface $stockRegistryInterface,
        Option $bundleOption,
        Type $bundleProductType,
        ProductOptionsRepositoryInterface $productOptionsRepository,
        Tax $weeTax,
        ProductSearchRepositoryInterface $productSearchRepository,
        PriceCollectionFactory $catalogRuleProductPricecollectionFactory,
        GiftCardPriceOptionsInterfaceFactory $giftCardPriceOptionsInterfaceFactory,
        GiftCardTemplateInterfaceFactory $giftCardTemplateInterfaceFactory,
        WebposManagementInterface $webposManagement,
        StockManagementInterface $stockManagement,
        ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $storeManager,
            $metadataService,
            $url,
            $productLink,
            $itemOptionFactory,
            $stockItemFactory,
            $catalogProductOptionFactory,
            $catalogProductVisibility,
            $catalogProductStatus,
            $catalogProductMediaConfig,
            $catalogProductType,
            $moduleManager,
            $catalogProduct,
            $resource,
            $resourceCollection,
            $collectionFactory,
            $filesystem,
            $indexerRegistry,
            $productFlatIndexerProcessor,
            $productPriceIndexerProcessor,
            $productEavIndexerProcessor,
            $categoryRepository,
            $imageCacheFactory,
            $entityCollectionProvider,
            $linkTypeProvider,
            $productLinkFactory,
            $productLinkExtensionFactory,
            $mediaGalleryEntryConverterPool,
            $dataObjectHelper,
            $joinProcessor,
            $data
        );
        $this->_configurableProduct = $configurableProduct;
        $this->_configurableProductBlock = $configurableProductBlock;
        $this->_helperImage = $helperImage;
        $this->_emulation = $emulation;
        $this->_formatInterface = $formatInterface;
        $this->_priceCurrencyInterface = $priceCurrencyInterface;
        $this->_stockItemRepositoryInterFace = $stockItemRepositoryInterFace;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_webposHelper = $webposHelper;
        $this->_productFactory = $productFactory;
        $this->_groupedProductType = $groupedProductType;
        $this->_stockRegistryInterface = $stockRegistryInterface;
        $this->_bundleOption = $bundleOption;
        $this->_bundleProductType = $bundleProductType;
        $this->productOptionsRepository = $productOptionsRepository;
        $this->weeTax = $weeTax;
        $this->productSearchRepository = $productSearchRepository;
        $this->catalogRuleProductPricecollectionFactory = $catalogRuleProductPricecollectionFactory;
        $this->giftCardPriceOptionsInterfaceFactory = $giftCardPriceOptionsInterfaceFactory;
        $this->giftCardTemplateInterfaceFactory = $giftCardTemplateInterfaceFactory;
        $this->webposManagement = $webposManagement;
        $this->stockManagement = $stockManagement;
        $this->objectManager = $objectManager;
    }

    /**
     * @var \Magento\Catalog\Model\Product
     */
    protected $_product;

    /**
     * @var
     */
    protected $_chidrenCollection;

    /**
     * Get product all options
     *
     * @return ProductOptionsInterface
     */
    public function getProductAllOptions()
    {
        if (!$this->_productOptions) {
            if ($this->getTypeId() == Configurable::TYPE_CODE) {
                $this->_productOptions = $this->productOptionsRepository->getProductOptions($this->getId());
            } else {
                $om = ObjectManager::getInstance();
                /** @var ProductOptionsInterface $productOptions */
                $productOptions = $om->create(ProductOptionsInterface::class);
                $productOptions->setConfigOption([]);

                $this->_productOptions = $productOptions;
            }
        }
        return $this->_productOptions;
    }

    /**
     * Get product
     *
     * @return \Magento\Catalog\Model\Product|Product
     */
    public function getProduct()
    {
        if (!$this->_product) {
            $this->_product = $this->load($this->getId());
        }
        return $this->_product;
    }

    /**
     * After load model
     *
     * @return Product
     */
    public function afterLoad()
    {
        $this->_product = $this;
        return parent::afterLoad();
    }

    /**
     * Product short description
     *
     * @return string|null
     */
    public function getShortDescription()
    {
        return $this->getData(self::SHORT_DESCRIPTION);
    }

    /**
     * Product description
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * Get base cost
     *
     * @return float|null
     */
    public function getBaseCost()
    {
        return $this->getData(self::BASE_COST);
    }

    /**
     * Retrieve images
     *
     * @return array/null
     */
    public function getImages()
    {
        $product = $this->getProduct();
        $images = [];
        if (!empty($product->getMediaGallery('images'))) {
            foreach ($product->getMediaGallery('images') as $image) {
                if ((isset($image['disabled']) && $image['disabled']) || empty($image['value_id'])) {
                    continue;
                }
                $images[] = $this->getMediaConfig()->getMediaUrl($image['file']);
            }
        }
        return $images;
    }

    /**
     * Sets product image from it's child if possible
     *
     * @return string
     */
    public function getImage()
    {
        $storeId = $this->_storeManager->getStore()->getId();
        $this->_emulation->startEnvironmentEmulation($storeId, Area::AREA_FRONTEND, true);
        $imgUrl = $this->_helperImage
            ->init($this, 'category_page_grid')
            ->constrainOnly(true)
            ->keepAspectRatio(true)
            ->keepTransparency(true)
            ->keepFrame(false)
            ->resize(310, 350)
            ->getUrl();
        if (!$this->_registry->registry('wp_is_search_product') &&
            (!$imgUrl || $imgUrl == $this->_helperImage->getDefaultPlaceholderUrl())
        ) {
            $images = $this->getImages();
            if (count($images)) {
                $imgUrl = $images[0];
            } else {
                $imgUrl = '';
            }
        }
        $this->_emulation->stopEnvironmentEmulation();
        return $imgUrl;
    }

    /**
     * @inheritdoc
     */
    public function getIsOptions()
    {
        if ($this->_registry->registry('wp_is_show_options')) {
            return $this->getProductAllOptions()->getIsOptions();
        } else {
            return 0;
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfigOption()
    {
        if ($this->_registry->registry('wp_is_show_options')) {
            return $this->getProductAllOptions()->getConfigOption();
        }
    }

    /**
     * Composes configuration for js
     *
     * @return string|void
     */
    public function getJsonConfig()
    {
        /** if product is configurable */
        if ($this->getTypeId() == Configurable::TYPE_CODE) {
            $this->_configurableProductBlock->setProduct($this);
            return $this->_configurableProductBlock->getJsonConfig();
        }
    }

    /**
     * Get JSON encoded configuration array which can be used for JS dynamic
     *
     * Price calculation depending on product options
     *
     * @return string
     */
    public function getPriceConfig()
    {
        if ($this->getTypeId() != Configurable::TYPE_CODE) {
            return;
        }

        /** @var EncoderInterface $jsonEncoder */
        $jsonEncoder = ObjectManager::getInstance()->get(
            EncoderInterface::class
        );
        if (!$this->hasOptions()) {
            $config = [
                'productId' => $this->getId(),
                'priceFormat' => $this->_formatInterface->getPriceFormat()
            ];
            return $jsonEncoder->encode($config);
        }

        $tierPrices = [];
        $tierPricesList = $this->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        if (!empty($tierPricesList)) {
            foreach ($tierPricesList as $tierPrice) {
                $value = ($tierPrice['price']) ? $tierPrice['price']->getValue() : 0;
                $tierPrices[] = $this->_priceCurrencyInterface->convert($value);
            }
        }
        $oldPrice = ($this->getPriceInfo()->getPrice('regular_price')->getAmount()) ?
            $this->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue() : 0;
        $finalPrice = ($this->getPriceInfo()->getPrice('final_price')->getAmount()) ?
            $this->getPriceInfo()->getPrice('final_price')->getAmount()->getValue() : 0;

        $config = [
            'productId' => $this->getId(),
            'priceFormat' => $this->_formatInterface->getPriceFormat(),
            'prices' => [
                'oldPrice' => [
                    'amount' => $this->_priceCurrencyInterface->convert(
                        $oldPrice
                    ),
                    'adjustments' => []
                ],
                'basePrice' => [
                    'amount' => $this->_priceCurrencyInterface->convert(
                        $finalPrice
                    ),
                    'adjustments' => []
                ],
                'finalPrice' => [
                    'amount' => $this->_priceCurrencyInterface->convert(
                        $finalPrice
                    ),
                    'adjustments' => []
                ]
            ],
            'idSuffix' => '_clone',
            'tierPrices' => $tierPrices
        ];

        return $jsonEncoder->encode($config);
    }

    /**
     * Get gift card price config
     *
     * @return GiftCardPriceOptionsInterface
     */
    public function getGiftCardPriceConfig()
    {
        if (ObjectManager::getInstance()->get(Data::class)
            ->isEnabledGiftCard()) {
            if ($this->getTypeId() != Giftvoucher::GIFT_CARD_TYPE) {
                return;
            }
        } else {
            return;
        }
        $giftCardPriceConfig = $this->giftCardPriceOptionsInterfaceFactory->create();
        $giftCardPriceConfig->setGiftCardType($this->getData(GiftCardPriceOptionsInterface::GIFT_CARD_TYPE));
        $giftCardPriceConfig->setGiftType($this->getData(GiftCardPriceOptionsInterface::GIFT_TYPE));
        $giftCardPriceConfig->setGiftValue($this->getData(GiftCardPriceOptionsInterface::GIFT_VALUE));
        $giftCardPriceConfig->setGiftFrom($this->getData(GiftCardPriceOptionsInterface::GIFT_FROM));
        $giftCardPriceConfig->setGiftTo($this->getData(GiftCardPriceOptionsInterface::GIFT_TO));
        $giftCardPriceConfig->setGiftDropdown($this->getData(GiftCardPriceOptionsInterface::GIFT_DROPDOWN));
        $giftCardPriceConfig->setGiftPriceType($this->getData(GiftCardPriceOptionsInterface::GIFT_PRICE_TYPE));
        $giftCardPriceConfig->setGiftPrice($this->getData(GiftCardPriceOptionsInterface::GIFT_PRICE));
        $templateIds = $this->getData('gift_template_ids');
        $templateIdsArray = explode(',', (string)$templateIds);
        $templates = [];
        foreach ($templateIdsArray as $templateId) {
            $giftCardTemplate = $this->giftCardTemplateInterfaceFactory->create();
            $giftCardTemplate->setTemplateId($templateId);
            $templateModel = ObjectManager::getInstance()->create(
                GiftTemplate::class
            )->load($templateId);
            $imagesUrlArray = [];
            if ($templateModel->getId() && $templateModel->getImages()) {
                $images = $templateModel->getImages();
                $imagesArray = explode(',', (string)$images);
                foreach ($imagesArray as $images) {
                    $imagesUrlArray[] = $images;
                }
                $giftCardTemplate->setImages($imagesUrlArray);
            } else {
                $giftCardTemplate->setImages([]);
            }

            $templates[] = $giftCardTemplate;
        }

        $giftCardPriceConfig->setTemplates($templates);
        return $giftCardPriceConfig;
    }

    /**
     * Return true if product has options
     *
     * @return bool
     */
    public function hasOptions()
    {
        if ($this->getTypeInstance()->hasOptions($this)) {
            return true;
        }

        if ($this->getTypeId() == 'giftvoucher') {
            $giftType = $this->getGiftType();
            if (!$giftType) {
                $giftType = $this->getResource()->getAttributeRawValue(
                    $this->getId(),
                    'gift_type',
                    $this->getStoreId()
                );
            }
            return $giftType != 1;
        }

        if ($this->getTypeId() == Grouped::TYPE_CODE) {
            return true;
        }

        return false;
    }

    /**
     * Get stock item
     *
     * @return \Magento\CatalogInventory\Api\Data\StockItemInterface|mixed
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getStockItem()
    {
        if ($this->getData('stock_item')) {
            return $this->getData('stock_item');
        }
        $stockItem = $this->_stockRegistryInterface->getStockItem($this->getId());

        if ($this->webposManagement->isMSIEnable()) {
            // correct stock qty with MSI
            $stockId = $this->stockManagement->getStockId();
            $getProductSalableQty = $this->objectManager
                ->create(GetProductSalableQtyInterface::class);
            $getStockItemConfiguration = $this->objectManager
                ->create(GetStockItemConfigurationInterface::class);
            if ($stockId) {
                /* Get Quantity from Source */
                $sourceCodes = $this->stockManagement->getLinkedSourceCodesByStockId($stockId);
                if (!empty($sourceCodes) && count($sourceCodes)) {
                    if (!$this->isComposite()) {
                        $searchCriteria = $this->_searchCriteriaBuilder
                            ->addFilter(SourceItemInterface::SKU, $this->getSku())
                            ->addFilter(SourceItemInterface::SOURCE_CODE, current($sourceCodes))
                            ->create();
                        /** @var SourceItemRepositoryInterface $sourceItemRepository */
                        $sourceItemRepository = $this->objectManager->create(SourceItemRepositoryInterface::class);
                        $sourceItemsOfProduct = $sourceItemRepository->getList($searchCriteria)->getItems();
                        if (count($sourceItemsOfProduct)) {
                            $stockItem->setQuantity(
                                current($sourceItemsOfProduct)->getQuantity()
                            );

                            $stockItem->setIsInStock(
                                current($sourceItemsOfProduct)->getStatus()
                            );
                        } else {
                            return [];
                        }
                    } else {
                        $stockItem->setQuantity(0);
                    }
                }
                /* End */

                $posHelper = $this->objectManager->create(Data::class);
                /** @var LocationRepositoryInterface $locationRepository */
                $locationRepository = $this->objectManager->create(LocationRepositoryInterface::class);
                $locationId = $posHelper->getCurrentLocationId();
                $locationData = $locationRepository->getById($locationId);
                $qty = 0;
                if ($locationData && $locationData->getStockId()) {
                    try {
                        $stockItemConfiguration = $getStockItemConfiguration->execute(
                            $this->getSku(),
                            $locationData->getStockId()
                        );
                        $isManageStock = $stockItemConfiguration->isManageStock();
                        $qty = $isManageStock ?
                            round($getProductSalableQty->execute($this->getSku(), $locationData->getStockId()), 4) : 0;

                    } catch (Exception $error) {
                        $qty = 0;
                    }
                }
                $stockItem->setQty($qty);
            } else {
                $stockItem->setQty(0);
            }
        }

        $this->getData('stock_item', $stockItem);
        return $stockItem;
    }

    /**
     * Check product in stock
     *
     * @param int $productId
     * @return bool
     */
    public function checkStock($productId)
    {
        $inStock = true;
        $product = $this->_productFactory->create()->load($productId);
        if (!$product->getId()) {
            $inStock = false;
        } else {
//            $stockData = $this->_stockRegistryInterface->getStockItem($product->getId());
            $stockData = $this->getStockItem();
            if ($stockData->getData('manage_stock') && !$stockData->getData('backorders')
                && ($stockData->getData('qty') <= 0 || !$stockData->getData('is_in_stock'))
            ) {
                $inStock = false;
            }
        }
        return $inStock;
    }

    /**
     * Retrieve assigned category Ids
     *
     * @return string
     */
    public function getCategoryIds()
    {
        if (!$this->hasData('category_ids')) {
            $wasLocked = false;
            if ($this->isLockedAttribute('category_ids')) {
                $wasLocked = true;
                $this->unlockAttribute('category_ids');
            }
            //$ids = $this->_getResource()->getCategoryIds($this);
            $ids = $this->getShowedCategoryIds();
            $this->setData('category_ids', $ids);
            if ($wasLocked) {
                $this->lockAttribute('category_ids');
            }
        }

        if (is_array($this->_getData('category_ids')) && count($this->_getData('category_ids'))) {
            $catStrings = '';
            foreach ($this->_getData('category_ids') as $catId) {
                $catStrings .= '\'' . $catId . '\'';
            }
            return $catStrings;
        }

        if (!is_array($this->_getData('category_ids'))) {
            return $this->_getData('category_ids');
        }

        return '';
    }

    /**
     * Retrieve product tax class id
     *
     * @return int
     */
    public function getTaxClassId()
    {
        return $this->getData(self::TAX_CLASS_ID);
    }

    /**
     * Get attributes to search
     *
     * @return array
     */
    public function getAttributesToSearch()
    {
        return $this->productSearchRepository->getSearchAttributes();
    }

    /**
     * Get search string to search product
     *
     * @return string
     */
    public function getSearchString()
    {
        $searchString = '';
        $attributesToSearch = $this->getAttributesToSearch();
        if (!empty($attributesToSearch)) {
            foreach ($attributesToSearch as $attribute) {
                if ($this->getData($attribute) && !is_array($this->getData($attribute))) {
                    $searchString .= $this->getData($attribute) . ' ';
                }
            }
        }
        $barcodes = $this->getBarCodeByProduct();
        if (!empty($barcodes)) {
            foreach (explode(',', $barcodes) as $barcode) {
                if (!empty($barcode)) {
                    $searchString .= ' ' . $barcode;
                }
            }
        }
        return $searchString;
    }

    /**
     * Get barcode by product
     *
     * @param Product|null $product
     * @return string
     */
    public function getBarCodeByProduct($product = null)
    {
        if (!$product) {
            $product = $this;
        }
        if ($this->getData('barcode_by_product') === null) {
            $barcode = "";
            $barcodeObject = new DataObject();
            $barcodeObject->setBarcode($barcode);
            ObjectManager::getInstance()
                ->create(ManagerInterface::class)
                ->dispatch(
                    'webpos_product_get_barcode_after',
                    ['object_barcode' => $barcodeObject, 'product' => $product]
                );
            $barcode = $barcodeObject->getBarcode();
            $this->setData('barcode_by_product', $barcode);
        }
        return $this->getData('barcode_by_product');
    }

    /**
     * Get children collection of configurable product
     *
     * @return \Magento\ConfigurableProduct\Model\ResourceModel\Product\Type\Configurable\Product\Collection
     */
    public function getChildrenCollection()
    {
        if (!$this->_chidrenCollection) {
            $collection = $this->_configurableProduct->getUsedProductCollection($this);
            $this->_chidrenCollection = $collection;
        }
        return $this->_chidrenCollection;
    }

    /**
     * Get All category ids include anchor categories
     *
     * @return array
     */
    public function getShowedCategoryIds()
    {
        Profiler::start('categories_' . $this->getEntityId());
        $categoryCollection = $this->getCategoryCollection();
        $categoryIds = $categoryCollection->getAllIds();
        $anchorIds = [];
        foreach ($categoryCollection as $category) {
            $pathIds = $category->getPathIds();
            array_pop($pathIds);
            //phpcs:ignore Magento2.Performance.ForeachArrayMerge
            $anchorIds = array_unique(array_merge($anchorIds, $pathIds));
        }
        $anchorCollection = $this->_categoryCollectionFactory->create()
            ->addFieldToFilter('is_anchor', 1)
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['in' => $anchorIds]);
        Profiler::stop('categories_' . $this->getEntityId());
        return array_unique(array_merge($categoryIds, $anchorCollection->getAllIds()));
    }

    /**
     * Get is virtual
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsVirtual()
    {
        $virtualTypes = [
            'customercredit', 'virtual'
        ];
        if (in_array($this->getTypeId(), $virtualTypes)
            || (ObjectManager::getInstance()->get(Data::class)->isEnabledGiftCard() &&
                $this->getTypeId() == Giftvoucher::GIFT_CARD_TYPE
                && $this->getGiftCardType() != GiftCardTypeOptions::TYPE_PHYSICAL
            )
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get product qty increment
     *
     * @return float|null
     */
    public function getQtyIncrement()
    {
        $qtyIncrement = 0;
        $product = $this->getProduct();
        $stockItem = $product->getStockItem();
        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $stockItem = $extendedAttributes->getStockItem();
        }
        $stockData = $stockItem->getData();
        if (is_array($stockData) && array_key_exists('enable_qty_increments', $stockData)
            && $stockData['enable_qty_increments'] == 1) {
            if (array_key_exists('qty_increments', $stockData) && $stockData['qty_increments'] > 0) {
                $qtyIncrement = $stockData['qty_increments'];
            }
        }
        return $qtyIncrement;
    }

    /**
     * Get is in stock
     *
     * @return int
     */
    public function getIsInStock()
    {
        $product = $this->getProduct();
        $stockItem = $product->getStockItem();
        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $stockItem = $extendedAttributes->getStockItem();
        }
        $stockData = $stockItem->getData();
        $isInStock = $this->getData(self::IS_IN_STOCK);
        if ($isInStock == null) {
            if (array_key_exists(self::IS_IN_STOCK, $stockData)) {
                $isInStock = $stockData[self::IS_IN_STOCK];
            }
        }
        return $isInStock;
    }

    /**
     * Get minimum qty
     *
     * @return float|null
     */
    public function getMinimumQty()
    {
        $product = $this->getProduct();
        $stockItem = $product->getStockItem();
        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $stockItem = $extendedAttributes->getStockItem();
        }
        $stockData = $stockItem->getData();
        $qty = $this->getData(self::MIN_SALE_QTY);
        if (!$qty) {
            if (array_key_exists(self::MIN_SALE_QTY, $stockData)) {
                $qty = $stockData[self::MIN_SALE_QTY];
            }
        }
        return $qty;
    }

    /**
     * Get maximum float|null
     *
     * @return float|null
     */
    public function getMaximumQty()
    {
        $product = $this->getProduct();
        $stockItem = $product->getStockItem();
        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $stockItem = $extendedAttributes->getStockItem();
        }
        $stockData = $stockItem->getData();
        $qty = $this->getData(self::MAX_SALE_QTY);
        if (!$qty) {
            if (array_key_exists(self::MAX_SALE_QTY, $stockData)) {
                $qty = $stockData[self::MAX_SALE_QTY];
            }
        }
        return $qty;
    }

    /**
     * Get qty
     *
     * @return string
     */
    public function getQty()
    {
        $qty = $this->getData(self::QTY);
        if (!$qty) {
            $stockItem = null;
            $extendedAttributes = $this->getExtensionAttributes();
            if ($extendedAttributes !== null) {
                $stockItem = $extendedAttributes->getStockItem();
            }
            if (!$stockItem) {
                $product = $this->getProduct();
                $stockItem = $product->getStockItem();
            }
            $stockData = $stockItem->getData();
            if (array_key_exists(self::QTY, $stockData)) {
                $qty = $stockData[self::QTY];
            }
        }
        return $qty;
    }

    /**
     * Get stock data by product
     *
     * @return StockItemInterface[]|null
     */
    public function getStocks()
    {
        if ($this->_registry->registry('wp_is_show_stock')) {
            $stocks = [];

            $stockItem = $this->getStockItem();
            if (!empty($stockItem)) {
                $om = ObjectManager::getInstance();
                /** @var StockItemInterface $item */
                $item = $om->create(StockItemInterface::class);
                $item->setItemId($stockItem->getItemId());
                $item->setProductId($stockItem->getProductId());
                $item->setStockId($stockItem->getStockId());
                $item->setSku($this->getSku());
                $item->setName($this->getName());
                $item->setQty($stockItem->getQty());
                $item->setIsInStock($stockItem->getIsInStock());
                $item->setUseConfigManageStock($stockItem->getUseConfigManageStock());
                $item->setManageStock($stockItem->getManageStock());
                $item->setBackorders($stockItem->getBackorders());
                $item->setUseConfigBackorders($stockItem->getUseConfigBackorders());
                $item->setMinQty($stockItem->getMinQty());
                $item->setUseConfigMinQty($stockItem->getUseConfigMinQty());
                $item->setMinSaleQty($stockItem->getMinSaleQty());
                $item->setUseConfigMinSaleQty($stockItem->getUseConfigMinSaleQty());
                $item->setMaxSaleQty($stockItem->getMaxSaleQty());
                $item->setUseConfigMaxSaleQty($stockItem->getUseConfigMaxSaleQty());
                $item->setUpdatedTime($this->getUpdatedAt());
                $item->setUseConfigQtyIncrements($stockItem->getUseConfigQtyIncrements());
                $item->setQtyIncrements($stockItem->getQtyIncrements());
                $item->setIsQtyDecimal($stockItem->getIsQtyDecimal());
                $item->setUseConfigEnableQtyInc($stockItem->getUseConfigEnableQtyInc());
                $item->setEnableQtyIncrements($stockItem->getEnableQtyIncrements());
                $item->setQuantity($stockItem->getQuantity());

                $stocks[] = $item;

                return $stocks;
            }
        }
        return null;
    }

    /**
     * Get is in stock
     *
     * @return int
     */
    public function getBackorders()
    {
        return $this->getData('backorders');
    }

    /**
     * Get final price
     *
     * @param float|null $qty
     * @return float|int
     * @throws NoSuchEntityException
     */
    public function getFinalPrice($qty = null)
    {
        if ($this->getTypeId() == Configurable::TYPE_CODE) {
            $rate = 1;
            if ($this->_storeManager->getStore()->getCurrentCurrencyRate()) {
                $rate = $this->_storeManager->getStore()->getCurrentCurrencyRate();
            }
            $priceConverted = $this->getPriceModel()->getFinalPrice($qty, $this) / $rate;
            return $priceConverted;
        }
        return parent::getFinalPrice($qty);
    }

    /**
     * Get qty increments
     *
     * @return string
     */
    public function getQtyIncrements()
    {
        return $this->getData(self::QTY_INCREMENTS);
    }

    /**
     * Get enable qty increments
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getEnableQtyIncrements()
    {
        return $this->getData(self::ENABLE_QTY_INCREMENTS);
    }

    /**
     * Get is qty decimal
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsQtyDecimal()
    {
        $product = $this->getProduct();
        $stockItem = $product->getStockItem();
        $extendedAttributes = $this->getExtensionAttributes();
        if ($extendedAttributes !== null) {
            $stockItem = $extendedAttributes->getStockItem();
        }
        $stockData = $stockItem->getData();
        $isQtyDecimal = $this->getData(self::IS_QTY_DECIMAL);
        if (!$isQtyDecimal) {
            if (array_key_exists(self::IS_QTY_DECIMAL, $stockData)) {
                $isQtyDecimal = $stockData[self::IS_QTY_DECIMAL];
            }
        }
        return $isQtyDecimal;
    }

    /**
     * Get product price through type instance
     *
     * @return float
     */
    public function getPrice()
    {
        if ($this->getTypeId() == Type::TYPE_CODE
            && $this->_registry->registry('wp_is_search_product')
        ) {
            if ($this->_calculatePrice || !$this->getData(self::PRICE)) {
                $product = $this->_productFactory->create();
                $product->setData($this->getData());
                return $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue();
            }
        }
        return parent::getPrice();
    }

    /**
     * Get data of children product
     *
     * @return ProductOriginalInterface[]|null
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getChildrenProducts()
    {
        if ($this->_registry->registry('wp_is_search_product')) {
            return null;
        }
        if ($this->getTypeId() == Configurable::TYPE_CODE) {
            if ($this->_registry->registry('webpos_get_product_by_id')) {
                return null;
            }
            $collection = $this->getChildrenCollection()->addAttributeToSelect(
                ['price', 'name', 'status', 'tax_class_id', 'special_price', 'special_from_date', 'special_to_date']
            );
            return $this->getChildrenProductsData($collection);
        } elseif ($this->getTypeId() == Type::TYPE_CODE) {
            $items = [];
            $productSKUs = [];
            $bunleOptions = $this->getExtensionAttributes()->getBundleProductOptions();
            if ($bunleOptions && !empty($bunleOptions)) {
                foreach ($bunleOptions as $_option) {
                    $productLinks = $_option->getProductLinks();
                    foreach ($productLinks as $_productLink) {
                        $productSKUs[] = $_productLink->getSku();
                    }
                }
                $collection = $this->getCollection()
                    ->addAttributeToFilter('sku', ['in' => $productSKUs])
                    ->addAttributeToSelect(
                        [
                            'price',
                            'name',
                            'status',
                            'tax_class_id',
                            'special_price',
                            'special_from_date',
                            'special_to_date'
                        ]
                    );
                return $this->getChildrenProductsData($collection);
            }
            return $items;
        } elseif ($this->getTypeId() == Grouped::TYPE_CODE) {
            $collection = $this->getTypeInstance()->getAssociatedProducts($this);
            return $this->getChildrenProductsData($collection);
        }
        return null;
    }

    /**
     * Get children products data
     *
     * @param array $collection
     * @return array
     */
    public function getChildrenProductsData($collection)
    {
        $items = [];
        $isSearchProduct = $this->_registry->registry('wp_is_search_product')
            || $this->_registry->registry('webpos_get_product_by_id');
        $isPriceTypeDynamic = $this->getTypeId() == Grouped::TYPE_CODE ||
            $this->getPriceType() == Price::PRICE_TYPE_DYNAMIC;
        foreach ($collection as $item) {
            $item->load($item->getId());
            $image = $item->getImage();
            if ($image) {
                $item->setImage($this->getMediaConfig()->getMediaUrl($image));
            }
            $fpt = $this->getChildrenFpt($item);
            if ($fpt) {
                $item->setFpt($fpt);
            }
            if ($isSearchProduct && $isPriceTypeDynamic) {
                $catalogRulePrices = $this->getCatalogruleChildrenPrices($item);
                $item->setCatalogrulePrices($catalogRulePrices);
            }
            $item->setData('base_cost', $item->getCost());
            $items[] = $item;
        }
        return $items;
    }

    /**
     * Get qty in online mode
     *
     * @return float|null
     */
    public function getQtyOnline()
    {
        return $this->getQty();
    }

    /**
     * Get children FPT
     *
     * @param Product $child
     * @return array
     */
    public function getChildrenFpt($child)
    {
        $ftp = [];
        $weeTax = $this->weeTax->getWeeeTaxAttributeCodes();
        if (count($weeTax)) {
            foreach ($weeTax as $attribute) {
                $ftp[] = $child->getData($attribute);
            }
        }
        return $ftp;
    }

    /**
     * Get FPT
     *
     * @return array
     */
    public function getFpt()
    {
        $ftp = [];
        $weeTax = $this->weeTax->getWeeeTaxAttributeCodes();
        if (count($weeTax)) {
            foreach ($weeTax as $attribute) {
                $ftp[] = $this->getData($attribute);
            }
        }
        return $ftp;
    }

    /**
     * Get shipment type
     *
     * @return int|null
     */
    public function getShipmentType()
    {
        if ($this->getTypeId() == Type::TYPE_CODE) {
            return $this->getData(self::SHIPMENT_TYPE);
        }
        return null;
    }

    /**
     * Get Price Type
     *
     * @return int|null
     */
    public function getPriceType()
    {
        return $this->getData(self::PRICE_TYPE);
    }

    /**
     * Get list of product options
     *
     * @return ProductCustomOptionInterface[]|null
     */
    public function getCustomOptions()
    {
        return $this->getProduct()->getOptions();
    }

    /**
     * Get product weight type
     *
     * @return int
     */
    public function getWeightType()
    {
        return $this->getData(self::WEIGHT_TYPE);
    }

    /**
     * Get weight
     *
     * @return float
     */
    public function getWeight()
    {
        return $this->getData(self::WEIGHT);
    }

    /**
     * Get Pos barcode
     *
     * @return string
     */
    public function getPosBarcode()
    {
        $objectManager = ObjectManager::getInstance();
        $helper = $objectManager->get(Data::class);
        $searchAttribute = $helper->getStoreConfig('webpos/product_search/barcode');
        $posBarcode = $this->getData($searchAttribute);
        $barcodes = $this->getBarCodeByProduct();
        if (!empty($barcodes)) {
            foreach (explode(',', $barcodes) as $barcode) {
                if (!empty($barcode)) {
                    $posBarcode .= ',' . $barcode;
                }
            }
        }
        $posBarcode = ',' . $posBarcode . ',';
        return $posBarcode;
    }

    /**
     * Get children special price
     *
     * @param Product $child
     * @return float
     */
    public function getChildrenSpecialPrice($child)
    {
        return $child->getSpecialPrice();
    }

    /**
     * Get catalog rule product prices
     *
     * @return PriceInterface[]|null
     */
    public function getCatalogrulePrices()
    {
        if ($this->_registry->registry('wp_is_search_product')
            || $this->_registry->registry('webpos_get_product_by_id')
        ) {
            /** @var Collection $collection */
            $collection = $this->catalogRuleProductPricecollectionFactory->create();
            $collection->addFieldToFilter(
                PriceInterface::PRODUCT_ID,
                $this->getId()
            );
            return $collection->getItems();
        }
        return null;
    }

    /**
     * Get catalog rule product prices
     *
     * @param DataObject $children
     * @return PriceInterface[]
     */
    public function getCatalogruleChildrenPrices($children)
    {
        /** @var Collection $collection */
        $collection = $this->catalogRuleProductPricecollectionFactory->create();
        $collection->addFieldToFilter(
            PriceInterface::PRODUCT_ID,
            $children->getId()
        );
        return $collection->getItems();
    }
}
