<?php

/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */
namespace Wyomind\DataFeedManager\Model;

use Wyomind\Framework\Helper\Progress as ProgressHelper;
/**
 *
 * @exclude_var e
 */
class Feeds extends \Magento\Framework\Model\AbstractModel
{
    public $logEnabled;
    public $customOptions;
    public $_relationShipResourceModel;
    public $_taxClassResourceModel;
    public $_imagesResourceModel;
    /* SYSTEM DEBUG/LOG */
    /**
     * @var string
     */
    private $template = "";
    /**
     * @var bool
     */
    public $isCron = false;
    /**
     * @var int
     */
    public $inc = 0;
    /**
     * @var
     */
    public $limit = INF;
    /**
     * @var \Wyomind\Framework\Helper\Progress
     */
    protected $progressHelper;
    /* System */
    /**
     * @var int
     */
    protected $_sqlSize = 1500;
    /**
     * @var bool
     */
    protected $_isPreview = false;
    /**
     * @var
     */
    protected $_tmpFile;
    /**
     * @var
     */
    protected $_filePath;
    /**
     * @var array
     */
    protected $_condition = ["eq" => "= '%s'", "neq" => "!= '%s'", "gteq" => ">= '%s'", "lteq" => "<= '%s'", "gt" => "> '%s'", "lt" => "< '%s'", "like" => "like '%s'", "nlike" => "not like '%s'", "null" => "is null", "notnull" => "is not null", "in" => "in (%s)", "nin" => "not in(%s)"];
    /**
     * @var int
     */
    protected $_counter = 0;
    /**
     * @var string
     */
    protected $_baseUrl = '';
    /**
     * @var array
     */
    protected $_allowedCurrencies = [];
    /**
     * @var int
     */
    protected $_itemInPreview = 10;
    /**
     * @var bool
     */
    public $_includeDisabled = false;
    /**
     * @var bool
     */
    protected $_includeInMenu = false;
    // params
    /**
     * @var array
     */
    public $params = [];
    // for attributes processing
    /**
     * @var string
     */
    public $backorders = '';
    // prices
    /**
     * @var array
     */
    public $tierPrices = [];
    // qty
    /**
     * @var string
     */
    public $manageStock = '';
    /**
     * @var array
     */
    public $configurableQty = [];
    // images
    /**
     * @var string
     */
    public $defaultImage = '';
    /**
     * @var string
     */
    public $baseImg = '';
    /**
     * @var array
     */
    public $gallery = [];
    // url
    /**
     * @var string
     */
    public $storeUrl = '';
    /**
     * @var int
     */
    public $urlRewrites = -1;
    // prices
    /**
     * @var string
     */
    public $priceIncludesTax = '';
    /**
     * @var string
     */
    public $defaultCurrency = '';
    /**
     * @var array
     */
    public $listOfCurrencies = [];
    /**
     * @var array
     */
    public $taxRates = [];
    /**
     * @var array
     */
    public $weeeTaxes = [];
    // categories
    /**
     * @var string
     */
    public $rootCategory = '';
    /**
     * @var array
     */
    public $categoriesFilterList = [];
    /**
     * @var array
     */
    public $categoriesMapping = [];
    /**
     * @var array
     */
    public $categories = [];
    /**
     * @var string
     */
    protected $_output = '';
    // data
    /**
     * @var array
     */
    public $listOfAttributes = [];
    /**
     * @var array
     */
    public $listOfAttributesType = [];
    /**
     * @var array
     */
    public $attributesLabelsList = [];
    /**
     * @var array
     */
    public $productRelationShip = [];
    /**
     * @var array
     */
    public $reviews = [];
    /**
     * @var array
     */
    protected $_attributeSets = [];
    /**
     * @var array
     */
    public $_configurableChildren = [];
    /**
     * @var array
     */
    public $_configurable = [];
    /**
     * @var array
     */
    public $_grouped = [];
    /**
     * @var array
     */
    public $_groupedChildren = [];
    /**
     * @var array
     */
    public $_bundle = [];
    /**
     * @var array
     */
    public $_bundleChildren = [];
    // status
    /**
     * @var string
     */
    protected $_flagDir = '/var/tmp/';
    /**
     * @var string
     */
    protected $_flagFile = '';
    // resourceModel
    /**
     * @var null|ResourceModel\TierPrice
     */
    protected $_tierPriceResourceModel = null;
    public $framework;
    /**
     * @var \Wyomind\DataFeedManager\Logger\Logger
     */
    public $logger;
    public $scopeConfig;
    public $objectManager;
    public $messageManager;
    public $coreDate;
    /**
     * @var \Magento\Store\Model\StoreFactory
     */
    public $storeFactory;
    public $directoryList;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    public $categoryFactory;
    /**
     * @var \Magento\Eav\Model\Entity\TypeFactory
     */
    public $attributeTypeFactory;
    /**
     * @var \Magento\Eav\Model\Entity\Attribute\SetFactory
     */
    public $attributeSetFactory;
    /**
     * @var \Magento\Eav\Model\Entity\AttributeFactory
     */
    public $attributeFactory;
    public $currency;
    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory
     */
    public $attributeOptionValueCollectionFactory;
    public $taxClassCollection;
    public $taxCalculation;
    public $taxCalculationRate;
    public $directoryRegion;
    public $customerGroup;
    /**
     * @var ResourceModel\Review\CollectionFactory
     */
    public $reviewCollectionFactory;
    /**
     * @var ResourceModel\Product\Option\CollectionFactory
     */
    public $productOptionCollectionFactory;
    /**
     * @var ResourceModel\Product\CollectionFactory
     */
    public $productCollectionFactory;
    /**
     * @var \Magento\CatalogRule\Model\ResourceModel\RuleFactory
     */
    public $ruleFactory;
    public $localeDate;
    public $customerSession;
    public $storeManager;
    public $filesystem;
    public $dfmHelper;
    public $parserHelper;
    public $storageHelper;
    public $attributesHelper;
    /**
     * @var ResourceModel\Functions\CollectionFactory
     */
    public $functionCollectionFactory;
    public $appResource;
    /**
     * @var \Magento\Framework\Event\ManagerInterface
     */
    public $eventManager;
    /**
     * @var ResourceModel\TaxClassFactory
     */
    public $taxClassResourceModelFactory;
    /**
     * @var ResourceModel\ImagesFactory
     */
    public $imagesResourceModelFactory;
    /**
     * @var ResourceModel\TierPriceFactory
     */
    public $tierPriceResourceModelFactory;
    /**
     * @var ResourceModel\RelationShipFactory
     */
    public $relationShipResourceModelFactory;
    public $weeeTaxResourceModel;
    public $io;
    // requirements
    /**
     * @var array
     */
    protected $_attributesRequired = [];
    /**
     * @var bool
     */
    protected $_requiresConfigurable = false;
    /**
     * @var bool
     */
    protected $_requiresBundle = false;
    /**
     * @var bool
     */
    protected $_requiresGrouped = false;
    /**
     * @var bool
     */
    protected $_loadOptions = false;
    /**
     * @var bool
     */
    protected $_loadImages = false;
    /**
     * @var bool
     */
    protected $_loadConfigurableQty = false;
    /**
     * @var bool
     */
    protected $_loadTierPrices = false;
    /**
     * @var bool
     */
    protected $_loadReviews = false;
    /**
     * @var bool
     */
    protected $_loadCategoriesUrl = false;
    /**
     * @var bool
     */
    protected $_loadRelationShip = false;
    /**
     * @var bool
     */
    protected $_loadStocks = [];
    /**
     * @var bool
     */
    protected $_loadSources = [];
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface|null
     */
    protected $_ioWrite = null;
    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface|null
     */
    protected $_ioRead = null;
    /**
     * @var ResourceModel\InventoryStock
     */
    protected $_inventoryStockResourceModel;
    /**
     * Collection of all stock inventory data
     * @var array|false
     */
    public $inventoryStocks = false;
    /**
     * Collection of all source inventory data
     * @var bool
     */
    public $inventorySources = false;
    /**
     * If the stock
     * @var int
     */
    public $stockId = 1;
    public function __construct(
        \Wyomind\DataFeedManager\Helper\Delegate $wyomind,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        /** @delegation off */
        \Magento\Store\Model\StoreFactory $storeFactory,
        /** @delegation off */
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Eav\Model\Entity\TypeFactory $attributeTypeFactory,
        \Magento\Eav\Model\Entity\Attribute\SetFactory $attributeSetFactory,
        \Magento\Eav\Model\Entity\AttributeFactory $attributeFactory,
        /** @delegation off */
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory $attributeOptionValueCollectionFactory,
        /** @delegation off */
        \Wyomind\DataFeedManager\Model\ResourceModel\Review\CollectionFactory $reviewCollectionFactory,
        \Wyomind\DataFeedManager\Model\ResourceModel\Product\Option\CollectionFactory $productOptionCollectionFactory,
        \Wyomind\DataFeedManager\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\CatalogRule\Model\ResourceModel\RuleFactory $ruleFactory,
        /** @delegation off */
        \Wyomind\DataFeedManager\Model\ResourceModel\Functions\CollectionFactory $functionCollectionFactory,
        /** @delegation off */
        \Wyomind\DataFeedManager\Model\ResourceModel\TaxClassFactory $taxClassResourceModelFactory,
        \Wyomind\DataFeedManager\Model\ResourceModel\ImagesFactory $imagesResourceModelFactory,
        \Wyomind\DataFeedManager\Model\ResourceModel\TierPriceFactory $tierPriceResourceModelFactory,
        \Wyomind\DataFeedManager\Model\ResourceModel\RelationShipFactory $relationShipResourceModelFactory,
        /** @delegation off */
        \Wyomind\DataFeedManager\Model\ResourceModel\InventoryStockFactory $inventoryStockResourceModelFactory,
        ?\Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        ?\Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    )
    {
        $wyomind->constructor($this, $wyomind, __CLASS__);
        $this->framework->constructor($this, func_get_args());
        $this->logger = $this->objectManager->create("\\Wyomind\\DataFeedManager\\Logger\\Logger");
        $this->progressHelper = $this->objectManager->create("\\Wyomind\\DataFeedManager\\Helper\\Progress");
        $this->storeFactory = $storeFactory;
        $this->categoryFactory = $categoryFactory;
        $this->attributeTypeFactory = $attributeTypeFactory;
        $this->attributeSetFactory = $attributeSetFactory;
        $this->attributeFactory = $attributeFactory;
        $this->attributeOptionValueCollectionFactory = $attributeOptionValueCollectionFactory;
        $this->reviewCollectionFactory = $reviewCollectionFactory;
        $this->productOptionCollectionFactory = $productOptionCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->ruleFactory = $ruleFactory;
        $this->functionCollectionFactory = $functionCollectionFactory;
        $this->eventManager = $context->getEventDispatcher();
        $this->taxClassResourceModelFactory = $taxClassResourceModelFactory;
        $this->imagesResourceModelFactory = $imagesResourceModelFactory;
        $this->tierPriceResourceModelFactory = $tierPriceResourceModelFactory;
        $this->relationShipResourceModelFactory = $relationShipResourceModelFactory;
        $this->_ioWrite = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->_ioRead = $this->filesystem->getDirectoryWrite(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $this->_imagesResourceModel = $imagesResourceModelFactory->create();
        $this->_taxClassResourceModel = $taxClassResourceModelFactory->create();
        $this->_tierPriceResourceModel = $tierPriceResourceModelFactory->create();
        $this->_relationShipResourceModel = $relationShipResourceModelFactory->create();
        $this->_inventoryStockResourceModel = $inventoryStockResourceModelFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }
    /**
     * Internal constructor/initializer
     */
    public function _construct()
    {
        $this->_init('Wyomind\\DataFeedManager\\Model\\ResourceModel\\Feeds');
    }
    /**
     * @param $pattern
     * @param string $escapedChar
     * @param string $escaper
     * @return mixed
     */
    public function escapeStr($pattern, $escapedChar = '"', $escaper = "\\")
    {
        return isset($pattern) && $pattern !== '' ? str_replace((string) $escapedChar, $escaper . (string) $escapedChar, $pattern) : '';
    }
    /**
     * @param $pattern
     * @param string $escapedChar
     * @return mixed
     */
    public function unescapeStr($pattern, $escapedChar = '"')
    {
        return str_replace('\\' . (string) $escapedChar, (string) $escapedChar, $pattern);
    }
    /**
     * Load custom functions from DB and instantiate them
     * @throws \Exception
     */
    public function loadCustomFunctions()
    {
        $displayErrors = ini_get('display_errors');
        ini_set('display_errors', 0);
        $collection = $this->functionCollectionFactory->create();
        foreach ($collection as $function) {
            if ($this->attributesHelper->execPhp($function->getScript(), "?>" . $function->getScript()) === false) {
                if ($this->_isPreview) {
                    ini_set('display_errors', $displayErrors);
                    throw new \Exception('Syntax error in ' . $function->getScript() . ' :' . error_get_last()['message']);
                } else {
                    ini_set('display_errors', $displayErrors);
                    $this->messageManager->addError("Syntax error in <i>" . $function->getScript() . "</i>
<br>" . error_get_last()['message']);
                    throw new \Exception();
                }
            }
        }
        ini_set('display_errors', $displayErrors);
    }
    /**
     * Generate google shopping data feed
     * @param object|null $request
     * @return \Wyomind\Datafeedmanager\Model\Feeds|string
     * @throws \Exception
     */
    public function generateFile($request = null)
    {
        try {
            if (php_sapi_name() != "cli") {
                session_write_close();
            }
            if ($this->_isPreview) {
                // set again the preview variable on preview context (workaround for delegation issue)
                $this->limit = $this->framework->getDefaultConfig('datafeedmanager/system/preview');
            }
            $connection = $this->getResource()->getConnection();
            $connection->query("SET SESSION group_concat_max_len = 10000;");
            $timeStart = time();
            $timeGlobal = 0;
            $this->progressHelper->startObservingProgress($this->isLogEnabled(), $this->getId(), $this->getName());
            $this->progressHelper->log("******************************* NEW PROCESS ******************************************", !$this->_isPreview);
            /* retrieve optional parameters from request or model */
            $this->extractParams($request);
            $this->progressHelper->log('Parameters loaded', !$this->_isPreview);
            // Add the helper with the model only now as we need the helper parameters
            $this->attributesHelper->setModel($this);
            // Data Feed disabled
            if ($this->params['status'] != 1 && !$this->_isPreview) {
                throw new \Exception(__('The data feed configuration must be enabled in order to generate a file.'));
            }
            $this->eventManager->dispatch('datafeedmanager_before_generate', ['datafeed' => $this]);
            $this->storeManager->setCurrentStore($this->params['store_id']);
            /* config variables */
            $this->extractConfiguration();
            $this->progressHelper->log('Configuration loaded', !$this->_isPreview);
            $this->progressHelper->log("START PROCESS FOR '" . strtoupper($this->getName()) . "'", !$this->_isPreview);
            /* set the memory limit size */
            $memoryLimit = $this->framework->getStoreConfig("datafeedmanager/system/memory_limit");
            ini_set('memorylimit', $memoryLimit . 'M');
            $this->progressHelper->log("Memory limit set to " . (int) $memoryLimit, !$this->_isPreview);
            /* open destination file */
            if (!$this->_isPreview) {
                $io = $this->openDestinationFile();
            }
            $this->progressHelper->log("File created ", !$this->_isPreview);
            /* Data Feed Headers */
            $headerPattern = $this->attributesHelper->executePhpScripts($this->_isPreview, $this->params['header'], null);
            if ($this->params['type'] == 1) {
                // xml
                $headerPattern = $this->encode($headerPattern);
            }
            if ($this->_isPreview) {
                if ($this->params['type'] == 1) {
                    // xml
                    $this->_output = $this->xmlEncloseData($headerPattern, $this->params['enclose_data'], $this->params['clean_data']);
                } else {
                    // others
                    $this->_output = $this->attributesHelper->executePhpScripts($this->_isPreview, $this->params['extra_header'], null);
                    $this->_output .= "
<table style='border:2px solid grey; font-family:arial; font-size:12px' cellspacing=0 cellpadding=0'>";
                    if ($this->params['include_header']) {
                        $this->_output .= $this->jsonToTable($headerPattern, true);
                    }
                }
            } else {
                if ($this->params['type'] == 1) {
                    // xml
                    $this->_output = $this->xmlEncloseData($headerPattern, $this->params['enclose_data'], $this->params['clean_data']) . "";
                } else {
                    // others
                    if ($this->params['extra_header'] != '') {
                        $this->_output = $this->encode($this->attributesHelper->executePhpScripts($this->_isPreview, $this->params['extra_header'], null) . "\r
");
                    }
                    if ($this->params['include_header']) {
                        $this->_output .= $this->encode($this->jsonToStr($headerPattern, $this->params['field_separator'], $this->params['field_protector'], $this->params['field_escape']));
                    }
                }
            }
            $this->progressHelper->log('Headers added', !$this->_isPreview);
            $display = '';
            if ($this->_isPreview) {
                $display = $this->encode($this->_output);
            } else {
                $io->write($this->encode($this->_output));
                $this->progressHelper->log("File is now locked", !$this->_isPreview, progressHelper::PROCESSING, 0);
            }
            $this->progressHelper->log('Flag set on PROCESSING', !$this->_isPreview);
            $this->_output = '';
            /* load custom functions */
            if (!$this->isCron) {
                $this->loadCustomFunctions();
                $this->progressHelper->log('Custom Functions loaded', !$this->_isPreview);
            }
            /* initialize store manager */
            $this->storeManager->setCurrentStore($this->params['store_id']);
            $this->progressHelper->log('Current store set on ' . $this->params['store_id'], !$this->_isPreview);
            /* analyze template to find what are the required attributes */
            $attributeCalls = $this->analyzeProductTemplate();
            $this->progressHelper->log('Template analyzed', !$this->_isPreview);
            $websiteId = $this->storeManager->getStore()->getWebsiteId();
            $this->extractWeeeTax($websiteId);
            $this->progressHelper->log('Weee Tax extracted', !$this->_isPreview);
            /* retrieve data feed categories configuration + all categories available in Magento */
            $this->extractCategories();
            $this->progressHelper->log('Categories extracted', !$this->_isPreview);
            $typeId = $this->getEntityTypeId();
            $this->progressHelper->log('EntityTypeIds collected', !$this->_isPreview);
            /* retrieve all attributes data */
            $this->extractAttributeList($typeId);
            $this->progressHelper->log('Attribute list collected', !$this->_isPreview);
            /* retrieve tax rates */
            $this->taxRates = $this->_taxClassResourceModel->getTaxRates();
            $this->progressHelper->log('Tax rates collected', !$this->_isPreview);
            /* retrieve reviews */
            if ($this->_loadReviews) {
                $this->reviews = $this->reviewCollectionFactory->create()->getReviews();
                $this->progressHelper->log('Reviews Collected', !$this->_isPreview);
            }
            /* extract images */
            if ($this->_loadImages) {
                $this->_imagesResourceModel->setStoreId($this->params['store_id']);
                $this->gallery = $this->_imagesResourceModel->getImages();
            }
            $this->progressHelper->log('Images collected', !$this->_isPreview);
            /* retrieve all attributes data */
            if ($this->dfmHelper->isMsiEnabled()) {
                /**Get all stocks*/
                $websiteId = $this->storeManager->getStore($this->getStoreId())->getWebsiteId();
                if (!empty($this->_loadStocks)) {
                    $this->inventoryStocks = $this->_inventoryStockResourceModel->create()->collectStocks($this->_loadStocks);
                }
                if (!empty($this->_loadSources)) {
                    $this->inventorySources = $this->_inventoryStockResourceModel->create()->collectSources($this->_loadSources);
                }
                $stockByWebsiteResolver = $this->objectManager->create('\\Magento\\InventorySales\\Model\\StockByWebsiteIdResolver');
                $this->stockId = $stockByWebsiteResolver->execute($websiteId)->getStockId();
                $this->progressHelper->log('Stock inventory collected', !$this->_isPreview);
            }
            switch ($this->urlRewrites) {
                case \Wyomind\DataFeedManager\Model\Config\UrlRewrite::PRODUCT_URL:
                    $notLike = "AND url.target_path NOT LIKE '%category%' and is_autogenerated = '1'";
                    $concat = 'MAX';
                    break;
                default:
                    $notLike = "AND url.target_path LIKE '%category%' and is_autogenerated = '1'";
                    $concat = 'GROUP_CONCAT';
                    break;
            }
            /* extract configurable product + children association */
            if ($this->_requiresConfigurable) {
                list($this->_configurable, $this->_configurableChildren) = $this->productCollectionFactory->create()->getConfigurableProducts($this->params['store_id'], $notLike, $concat, $this->listOfAttributes, $this->_includeDisabled);
                $this->progressHelper->log('Configurable product collected', !$this->_isPreview);
            }
            /* extract configurable quantities */
            if ($this->_loadConfigurableQty) {
                $this->configurableQty = $this->productCollectionFactory->create()->getConfigurableQuantities($this->params['store_id'], $this->_includeDisabled);
                $this->progressHelper->log('Qty for configurable prices collected', !$this->_isPreview);
            }
            if ($this->_loadRelationShip) {
                $this->productRelationShip = $this->_relationShipResourceModel->getRelationShip();
                $this->progressHelper->log('Relationships collected', !$this->_isPreview);
            }
            /* extract tier prices */
            if ($this->_loadTierPrices) {
                $websiteId = $this->storeManager->getStore()->getWebsiteId();
                $this->tierPrices = $this->_tierPriceResourceModel->getTierPrices($websiteId);
                $this->progressHelper->log('Tiers prices collected', !$this->_isPreview);
            }
            $this->extractAttributeSets();
            $this->progressHelper->log('Attribute sets collected', !$this->_isPreview);
            /* extract bundle products */
            if ($this->_requiresBundle) {
                list($this->_bundle, $this->_bundleChildren) = $this->productCollectionFactory->create()->getBundleProducts($this->params['store_id'], $notLike, $concat, $this->listOfAttributes, $this->_includeDisabled);
                $this->progressHelper->log('Bundle products collected', !$this->_isPreview);
            }
            /* extract grouped products */
            if ($this->_requiresGrouped) {
                list($this->_grouped, $this->_groupedChildren) = $this->productCollectionFactory->create()->getGroupedProducts($this->params['store_id'], $notLike, $concat, $this->listOfAttributes, $this->_includeDisabled);
                $this->progressHelper->log('Grouped products collected', !$this->_isPreview);
            }
            /* extract custom options */
            if ($this->_loadOptions) {
                $this->customOptions = $this->productOptionCollectionFactory->create()->getCustomOptions();
                $this->progressHelper->log('Custom options collected', !$this->_isPreview);
            }
            /* create main request to retrieve products */
            $mainCollection = $this->productCollectionFactory->create()->getMainRequest($this->params['store_id'], $this->storeManager->getStore()->getWebsiteId(), $notLike, $concat, $this->manageStock, $this->listOfAttributes, $this->categoriesFilterList, $this->_condition, $this->params, $this->_includeDisabled);
            $this->progressHelper->log('Main query built', !$this->_isPreview);
            /*******************************************************************
             * Extract all products
             *******************************************************************/
            $currentLoop = 0;
            // number of products to extract
            if ($this->limit != INF && $this->limit > 0) {
                // if limit is set
                $this->_counter = $this->limit;
            } else {
                $this->_counter = $this->productCollectionFactory->create()->getProductCount($this->params['store_id'], $this->storeManager->getStore()->getWebsiteId(), $notLike, $concat, $this->manageStock, $this->listOfAttributes, $this->categoriesFilterList, $this->_condition, $this->params, $this->_includeDisabled);
                $this->limit = $this->_counter;
            }
            $loops = ceil($this->_counter / $this->_sqlSize);
            $this->inc = 0;
            $this->progressHelper->log('Total items calculated (' . $this->_counter . ' in ' . $loops . ' queries )', !$this->_isPreview);
            $i = 1;
            $output = '';
            while ($currentLoop < $loops) {
                // limit the number of product in the result
                $mainCollection->setLimit($this->_sqlSize, $currentLoop);
                $mainCollection->clear();
                $currentLoop++;
                $limitTo = $this->_sqlSize * $currentLoop;
                if ($this->_sqlSize * $currentLoop > $this->_counter) {
                    $limitTo = $this->_counter;
                }
                $this->progressHelper->log("Fetching products from " . ($this->_sqlSize * ($currentLoop - 1) + 1) . " to " . $limitTo . " - iteration #{$currentLoop}", !$this->_isPreview, progressHelper::PROCESSING, round(100 * $i / $this->_counter));
                /* Product by product treatment */
                foreach ($mainCollection as $product) {
                    $this->attributesHelper->skip(false);
                    /* Initial pattern */
                    $productPattern = $this->template;
                    //$this->params['product_pattern'];
                    foreach ($attributeCalls as $pattern => $attributeCall) {
                        if (count($attributeCall) == 0) {
                            continue;
                        }
                        // si product.load_options => pattern duplication
                        if ($this->_loadOptions && $attributeCall[0]['object'] == "custom_options" && $attributeCall[0]['property'] == "iterate") {
                            $productPattern = $this->attributesHelper->loadOptions($product, $attributeCall[0]['parameters'], $productPattern);
                        }
                        $value = '';
                        $count = count($attributeCall);
                        for ($j = 0; $j < $count; $j++) {
                            $value = $this->attributesHelper->executeAttribute($attributeCall[$j], $product);
                            if ($attributeCall[$j]['or'] && !empty($value)) {
                                break;
                            }
                        }
                        if ($this->params['type'] > 1 && !$this->_isPreview) {
                            // xml
                            $value = $this->escapeStr($value, $this->params['field_protector'], $this->params['field_escape']);
                        }
                        $value = str_replace(["<", ">", '"', '\\'], ["__LOWERTHAN__", "__HIGHERTHAN__", "__QUOTES__", "__BACKSLASH__"], (string) $value);
                        $underscore = strpos($pattern, "_");
                        $pattern = substr($pattern, $underscore + 1);
                        if (strpos($pattern, "PHP_") === 0) {
                            $pattern = substr($pattern, 4);
                            $value = '"' . str_replace('"', '\\"', (string) $value) . '"';
                        }
                        //$value = str_replace(["<", ">", '"', "\\", "{", "}"], ["__LOWERTHAN__", "__HIGHERTHAN__", "__QUOTES__","__BACKSLASH__"], $value);
                        $productPattern = str_replace($pattern, $value, $productPattern);
                        //                        $productPattern = $this->dfmHelper->strReplaceFirst($pattern, $value, $productPattern);
                    }
                    $productPattern = $this->attributesHelper->executePhpScripts($this->_isPreview, $productPattern, $product);
                    if ($this->attributesHelper->getSkip()) {
                        continue;
                    }
                    if ($this->params['type'] == 1) {
                        // xml
                        if (!$this->_isPreview) {
                            $productPattern = $this->encode($productPattern);
                            $productPattern = $this->xmlEncloseData($productPattern, $this->params['enclose_data'], $this->params['clean_data']);
                        } else {
                            $productPattern = $this->xmlEncloseData($productPattern, $this->params['enclose_data'], $this->params['clean_data']);
                        }
                    } elseif ($this->params['type'] != 1) {
                        // others
                        if (!$this->_isPreview) {
                            $productPattern = $this->jsonToStr($productPattern, $this->params['field_separator'], $this->params['field_protector'], $this->params['field_escape']);
                            $productPattern = $this->encode($productPattern);
                        } else {
                            $productPattern = $this->jsonToTable($productPattern, false);
                        }
                    }
                    // Retrieve HTML tags
                    $productPattern = str_replace(["__LOWERTHAN__", "__HIGHERTHAN__", "__QUOTES__", "__BACKSLASH__"], ["<", ">", '"', '\\'], $productPattern);
                    // Data output foreach product
                    if (!empty($productPattern)) {
                        $this->inc++;
                        $output .= $productPattern;
                        // Output in file or display
                        if ($this->_isPreview) {
                            $display .= $output;
                            $output = '';
                        } else {
                            if ($i % $this->framework->getStoreConfig("datafeedmanager/system/buffer") == 0) {
                                $io->write($output);
                                unset($output);
                                $output = '';
                                $timeEnd = time();
                                $time = (int) $timeEnd - (int) $timeStart;
                                $timeGlobal += $time;
                                $timeStart = time();
                                $this->progressHelper->log($this->inc . "/" . $this->_counter . " items added", !$this->_isPreview, progressHelper::PROCESSING, round(100 * $i / $this->_counter));
                            }
                        }
                        // Break if the limit is reached
                        if ($this->limit && $i == $this->limit) {
                            break 2;
                        }
                        $i++;
                    }
                }
                // for each product
            }
            // while
            unset($mainCollection);
            // FOOTER
            if (!$this->_isPreview) {
                $io->write($output);
                if (strlen(trim((string) $this->params['footer'])) > 1) {
                    $io->write($this->params['footer'] . "
");
                }
                if ($this->params['extra_footer'] != '') {
                    $io->write($this->attributesHelper->executePhpScripts($this->_isPreview, $this->params['extra_footer'], null));
                }
            } else {
                $display .= $output;
                $display .= $this->params['footer'] . "
";
                if ($this->params['type'] > 1) {
                    // others
                    $display .= "
                        </table>";
                }
                $display .= $this->attributesHelper->executePhpScripts($this->_isPreview, $this->params['extra_footer'], null);
            }
            $this->progressHelper->log($this->inc . "/" . $this->_counter . " items added", !$this->_isPreview);
            if (!$this->_isPreview) {
                $io->write($this->_output);
            }
            $this->progressHelper->log("Export complete", !$this->_isPreview, progressHelper::SUCCEEDED, 100);
            if (!$this->_isPreview) {
                $io->close();
            }
            if ($this->_isPreview) {
                return $display;
            } else {
                $this->setUpdatedAt($this->coreDate->gmtDate('Y-m-d H:i:s'));
                $this->save();
                $this->io->open(['path' => $this->getFilePath()]);
                $finalFile = $this->dfmHelper->getFinalFilename($this->getDateformat(), $this->getName(), $this->getUpdatedAt()) . $this->dfmHelper->getExtFromType($this->getType());
                $this->io->mv($this->_tmpFile, $finalFile);
                if ($this->params['ftp_enabled']) {
                    $this->storageHelper->ftpUpload($this->params['use_sftp'], !$this->params['ftp_active'], $this->params['ftp_host'], $this->params['ftp_login'], $this->params['ftp_password'], $this->params['ftp_dir'], $this->params['ftp_ssl'], $this->getPath(), $finalFile, $this->params['ftp_port']);
                }
            }
            $this->progressHelper->stopObservingProgress();
            return $this;
        } catch (\Exception $e) {
            $this->progressHelper->log($e->getMessage(), !$this->_isPreview, progressHelper::FAILED);
            throw new \Exception($e->getMessage());
        }
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /* CORE FUNCTIONS                                                           */
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /**
     * @param $pattern
     * @param bool $header
     * @return null|string
     */
    public function jsonToTable($pattern, $header = false)
    {
        $pattern = preg_replace(['/(\\r\\n|\\n|\\r|\\r\\n)/s', '/[\\x00-\\x1f]/'], '', $pattern);
        $styleTd = 'padding:2px; border:1px solid grey; text-align:center;padding:5px; min-width:10px;min-height:10px;';
        $data = json_decode((string) $pattern);
        if (!is_array($data)) {
            $d[] = $data;
        } else {
            $d = $data;
        }
        $tr = null;
        foreach ($d as $data) {
            $br = 0;
            if (isset($data->header)) {
                $data = $data->header;
            } elseif (isset($data->product)) {
                $data = $data->product;
            } else {
                $data = [];
            }
            if ($header) {
                $tr = "
<tr style='background-color:grey; color:white; font-weight:bold'>";
            } else {
                $tr .= "
<tr>";
            }
            foreach ($data as $key => $value) {
                $value = $value;
                if ($br) {
                    $br++;
                }
                if (strstr($value, "/breakline/")) {
                    $value = str_replace("/breakline/", "
</tr>
<tr>", $value);
                    $br = 1;
                }
                $value != null ? $v = $value : ($v = "<span style='font-size:10px;color:grey'>(empty)</span>");
                $tr .= "
    <td style='" . $styleTd . "'>" . $v . "</td>
    ";
            }
            $tr .= "
</tr>";
        }
        return $tr;
    }
    /**
     * @param $pattern
     * @param $delimiter
     * @param $enclosure
     * @param $escaper
     * @return string
     */
    public function jsonToStr($pattern, $delimiter, $enclosure, $escaper)
    {
        $pattern = preg_replace(['/(\\r\\n|\\n|\\r|\\r\\n)/s', '/[\\x00-\\x1f]/'], '', $pattern);
        $data = json_decode((string) $pattern);
        if (!is_array($data)) {
            $d[] = $data;
        } else {
            $d = $data;
        }
        $line = '';
        if ($delimiter == '\\t') {
            $delimiter = "\t";
        }
        foreach ($d as $data) {
            $br = 0;
            if (isset($data->header)) {
                $data = $data->header;
            } else {
                if (!json_decode((string) $pattern)) {
                    return '';
                }
                $data = $data->product;
            }
            $u = 0;
            foreach ($data as $key => $value) {
                if ($br > 0) {
                    $br = 2;
                }
                if (strstr($value, "/breakline/")) {
                    $br++;
                }
                if ($u > 0 && $br < 2) {
                    $line .= $delimiter;
                }
                if (!strstr($value, "/breakline/")) {
                    $br = 0;
                }
                if ($enclosure != "") {
                    $line .= $enclosure . $this->escapeStr(str_replace("/breakline/", '', $value), $enclosure, $escaper) . $enclosure;
                    if (strstr($value, "/breakline/")) {
                        $line .= "\r
";
                    }
                } else {
                    $value = str_replace("/breakline/", "\r
", $value);
                    $line .= $this->escapeStr($value, $delimiter, $escaper);
                }
                $u++;
            }
            if ($delimiter == "[|]") {
                $line .= "[:]";
            }
            if (!$br) {
                $line .= "\r
";
            }
        }
        return $line;
    }
    /**
     * Open the destination file of the data feed if needed
     * @return \Magento\Framework\Filesystem\Io\File|null
     * @throws \Exception
     */
    public function openDestinationFile()
    {
        $io = null;
        $this->_ioWrite->create($this->getPath());
        // create path if not exists
        if (!is_writable($this->getFilePath())) {
            throw new \Exception(__('File "%1" cannot be saved.
<br/>Please, make sure the directory "%2" is writable by web server.', $this->getName(), $this->getFilePath()));
        } else {
            $this->_tmpFile = $this->getName() . $this->dfmHelper->getExtFromType($this->getType()) . ".tmp";
            $io = $this->_ioWrite->openFile($this->getPath() . "/" . $this->_tmpFile, 'w');
        }
        return $io;
    }
    /**
     * Open the flag file
     * @param string $mode 'w' for writing, 'r' for reading
     * @return mixed the file handler
     */
    private function openFlagFile($mode = 'w')
    {
        $this->_ioWrite->create($this->_flagDir);
        // create path if not exists
        if ($mode === 'w') {
            $io = $this->_ioWrite->openFile($this->_flagFile, $mode);
        } else {
            $io = $this->_ioRead->openFile($this->_flagFile, $mode);
        }
        return $io;
    }
    /**
     * Get the parent product if needed
     * @param string $reference
     * @param \Mage\Core\Catalog\Product $product
     * @return object
     */
    public function checkReference($reference, $product)
    {
        $productId = $product->getId();
        if ($productId == null) {
            // when the product is not associated to a category
            $productId = $product->getEntityId();
        }
        if (($reference == 'parent' || $reference == 'configurable') && isset($this->_configurable[$productId])) {
            return $this->_configurable[$productId];
        } elseif (($reference == 'parent' || $reference == 'grouped') && isset($this->_grouped[$productId])) {
            return $this->_grouped[$productId];
        } elseif (($reference == 'parent' || $reference == 'bundle') && isset($this->_bundle[$productId])) {
            return $this->_bundle[$productId];
        } elseif ($reference == 'product') {
            return $product;
        } else {
            return null;
        }
    }
    /**
     * Retrieve params from the request or from the model itself
     * @param $request
     */
    private function extractParams($request)
    {
        $resource = $this->appResource;
        $read = $resource->getConnection('core_read');
        $table = $resource->getTableName('datafeedmanager_feeds');
        $fields = $read->describeTable($table);
        foreach (array_keys($fields) as $field) {
            $this->params[$field] = $request !== null && (is_string($request->getParam($field)) || is_array($request->getParam($field))) ? $request->getParam($field) : $this->getData($field);
        }
        $this->progressHelper->log('Parameters collected', !$this->_isPreview);
    }
    /**
     *
     */
    private function extractAttributeSets()
    {
        $this->_attributeSets = [];
        $typeId = null;
        $resTypeId = $this->attributeTypeFactory->create()->getCollection()->addFieldToFilter('entity_type_code', ['eq' => 'catalog_product']);
        foreach ($resTypeId as $re) {
            $typeId = $re['entity_type_id'];
        }
        $tmp = $this->attributeSetFactory->create()->getCollection()->addFieldToFilter('entity_type_id', ['eq' => $typeId]);
        foreach ($tmp as $attributeSet) {
            $this->_attributeSets[$attributeSet->getId()] = $attributeSet->getName();
        }
    }
    /**
     * Retrieve the store configuration
     */
    private function extractConfiguration()
    {
        $this->logEnabled = $this->framework->getStoreConfig("datafeedmanager/system/log");
        $this->urlRewrites = $this->framework->getStoreConfig("datafeedmanager/system/urlrewrite");
        $this->defaultImage = $this->framework->getStoreConfig("catalog/placeholder/image_placeholder");
        if ($this->framework->getDefaultConfig('catalog/price/scope') == \Magento\Store\Model\Store::PRICE_SCOPE_WEBSITE) {
            $this->defaultCurrency = $this->framework->getStoreConfig("currency/options/base", $this->params['store_id']);
        } else {
            $this->defaultCurrency = $this->framework->getStoreConfig("currency/options/base", 0);
        }
        $this->manageStock = $this->framework->getStoreConfig("cataloginventory/item_options/manage_stock");
        $this->backorders = $this->framework->getStoreConfig("cataloginventory/item_options/backorders");
        $this->_sqlSize = $this->framework->getStoreConfig("datafeedmanager/system/sqlsize");
        $this->_includeInMenu = $this->framework->getStoreConfig("datafeedmanager/system/include_in_menu");
        $this->_baseUrl = $this->getStoreBaseUrl();
        $this->storeUrl = $this->getStoreUrl($this->params['store_id']);
        if ($this->storeManager->getStore()->isUseStoreInUrl()) {
            $this->storeUrl .= $this->storeManager->getStore()->getCode() . '/';
        }
        $this->baseImg = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, $this->framework->getStoreConfig("web/secure/use_in_frontend", $this->params['store_id']));
        $this->priceIncludesTax = $this->framework->getStoreConfig(\Magento\Tax\Model\Config::CONFIG_XML_PATH_PRICE_INCLUDES_TAX, $this->params['store_id']);
        $this->rootCategory = $this->storeManager->getStore()->getRootCategoryId();
        $this->_allowedCurrencies = $this->currency->getConfigAllowCurrencies();
        $this->listOfCurrencies = $this->currency->getCurrencyRates($this->defaultCurrency, array_values($this->_allowedCurrencies));
        $this->_itemInPreview = $this->framework->getStoreConfig("datafeedmanager/system/preview", $this->params['store_id']);
        $this->_includeDisabled = $this->framework->getStoreConfig("datafeedmanager/system/include_disabled", $this->params['store_id']);
    }
    /**
     *
     */
    public function extractWeeeTax()
    {
        /*
        [ attribute_code =>
        [ productId =>
        [ countryCode (FR) =>
        [
        regionCode (54) => tax value,
        regionCode (88) => tax value
        ],
        countryCode (DE) =>
        [
        regionCode (XX) => tax value,
        regionCode (YY) => tax value
        ]
        ],
        productId =>
        [ countryCode (FR) =>
        [
        regionCode (54) => tax value,
        regionCode (88) => tax value
        ],
        countryCode (DE) =>
        [
        regionCode (XX) => tax value,
        regionCode (YY) => tax value
        ]
        ]
        ]
        ]
        */
        $this->weeeTaxes = [];
        $weeeTaxes = $this->weeeTaxResourceModel->getAll($this->getStoreId());
        foreach ($weeeTaxes as $weeeTax) {
            if (!isset($this->weeeTaxes[$weeeTax['attribute_code']])) {
                $this->weeeTaxes[$weeeTax['attribute_code']] = [];
            }
            if (!isset($this->weeeTaxes[$weeeTax['attribute_code']][$weeeTax['entity_id']])) {
                $this->weeeTaxes[$weeeTax['attribute_code']][$weeeTax['entity_id']] = [];
            }
            if (!isset($this->weeeTaxes[$weeeTax['attribute_code']][$weeeTax['entity_id']][$weeeTax['country']])) {
                $this->weeeTaxes[$weeeTax['attribute_code']][$weeeTax['entity_id']][$weeeTax['country']] = [];
            }
            $this->weeeTaxes[$weeeTax['attribute_code']][$weeeTax['entity_id']][$weeeTax['country']][$weeeTax['region_code']] = $weeeTax['weee_value'];
        }
    }
    /**
     * Retrieve categories data (check/not checked, mapping, id)
     * + retrieve all categories available in Magento
     */
    private function extractCategories()
    {
        // 1. data feed configuration data
        $this->categoriesFilterList = [];
        $this->categoriesMapping = [];
        if (is_array(json_decode((string) $this->params['categories'], true))) {
            foreach (json_decode((string) $this->params['categories'], true) as $key => $categoriesFilter) {
                if (isset($categoriesFilter['c']) && $categoriesFilter['c'] == 1) {
                    $this->categoriesFilterList[] = $key;
                }
            }
            foreach (json_decode((string) $this->params['categories'], true) as $key => $categoriesFilter) {
                if (isset($categoriesFilter['m']) && $categoriesFilter['m'] != "") {
                    $this->categoriesMapping[$key] = $categoriesFilter['m'];
                }
            }
        }
        if (count($this->categoriesFilterList) < 1) {
            $this->categoriesFilterList[] = '*';
        }
        // 2. all categories available
        $listOfCategories = $this->categoryFactory->create()->getCollection()->setStoreId($this->params['store_id'])->addAttributeToSelect(['name', 'store_id', 'is_active', 'include_in_menu']);
        $this->categories = [];
        foreach ($listOfCategories as $category) {
            $this->categories[$category->getId()]['name'] = $category->getName();
            $this->categories[$category->getId()]['path'] = $category->getPath();
            $this->categories[$category->getId()]['level'] = $category->getLevel();
            if ($this->_loadCategoriesUrl) {
                $this->categories[$category->getId()]['url'] = $category->getUrl();
            }
            if ($this->_includeInMenu) {
                $this->categories[$category->getId()]['include_in_menu'] = true;
            } else {
                $this->categories[$category->getId()]['include_in_menu'] = $category->getIncludeInMenu();
            }
        }
        foreach ($this->categoriesFilterList as $i => $id) {
            if ($id != '*' && !array_key_exists($id, $this->categories)) {
                unset($this->categoriesFilterList[$i]);
            }
        }
        if (!count($this->categoriesFilterList)) {
            $this->categoriesFilterList = [0 => '*'];
        }
        $this->categoriesFilterList = array_values($this->categoriesFilterList);
    }
    /**
     * Analyse product template, then check what attributes are required
     */
    private function analyzeProductTemplate()
    {
        list($result, $template) = $this->parserHelper->extractAttributeCalls($this->params['product_pattern']);
        $this->template = $template;
        $this->_requiresConfigurable = false;
        $this->_requiresBundle = false;
        $this->_requiresGrouped = false;
        // check needed parent types & needed product attributes
        foreach ($result as $infos) {
            foreach ($infos as $info) {
                // check needed parent types
                switch ($info['object']) {
                    case 'parent':
                        $this->_requiresConfigurable = true;
                        $this->_requiresBundle = true;
                        $this->_requiresGrouped = true;
                        break;
                    case 'configurable':
                        $this->_requiresConfigurable = true;
                        break;
                    case 'bundle':
                        $this->_requiresBundle = true;
                        break;
                    case 'grouped':
                        $this->_requiresGrouped = true;
                        break;
                    case 'custom_options':
                        $this->_loadOptions = true;
                        break;
                }
                // check if statements
                if (isset($info['parameters']['if'])) {
                    foreach ($info['parameters']['if'] as $if) {
                        if (isset($if['object'])) {
                            switch ($if['object']) {
                                case 'parent':
                                    $this->_requiresConfigurable = true;
                                    $this->_requiresBundle = true;
                                    $this->_requiresGrouped = true;
                                    break;
                                case 'configurable':
                                    $this->_requiresConfigurable = true;
                                    break;
                                case 'bundle':
                                    $this->_requiresBundle = true;
                                    break;
                                case 'grouped':
                                    $this->_requiresGrouped = true;
                                    break;
                            }
                        }
                        if (isset($if['property'])) {
                            array_push($this->_attributesRequired, $if['property']);
                        }
                    }
                }
                // check product attributes
                switch ($info['property']) {
                    case 'tier_price':
                    case 'tier_price_qty':
                        $this->_loadTierPrices = true;
                        break;
                    case 'url':
                        array_push($this->_attributesRequired, 'url_key');
                        break;
                    case 'uri':
                        array_push($this->_attributesRequired, 'url_key');
                        break;
                    case 'item_group_id':
                        $this->_requiresConfigurable = true;
                        break;
                    case 'description':
                        array_push($this->_attributesRequired, 'short_description');
                        array_push($this->_attributesRequired, 'description');
                        break;
                    case 'image_link':
                        array_push($this->_attributesRequired, 'image');
                        array_push($this->_attributesRequired, 'small_image');
                        array_push($this->_attributesRequired, 'thumbnail');
                        if (isset($info['parameters']['role'])) {
                            array_push($this->_attributesRequired, $info['parameters']['role']);
                        }
                        $this->_loadImages = true;
                        break;
                    case 'review_count':
                    case 'review_average':
                        $this->_loadReviews = true;
                        break;
                    case 'availability':
                    case 'is_in_stock':
                    case 'qty':
                        if ($info['object'] == "configurable" || $this->params['type_ids'] == '*' || strpos($this->params['type_ids'], 'configurable') !== false) {
                            $this->_loadConfigurableQty = true;
                        }
                        if (isset($info['parameters']['stock_id'])) {
                            $this->_loadStocks[] = $info['parameters']['stock_id'];
                        }
                        if (isset($info['parameters']['source_code'])) {
                            $this->_loadSources[] = $info['parameters']['source_code'];
                        }
                        break;
                    case 'categories_url':
                        $this->_loadCategoriesUrl = true;
                        break;
                    case 'relation_ship':
                        $this->_loadRelationShip = true;
                        break;
                    case 'sc_images':
                        array_push($this->_attributesRequired, 'image');
                        array_push($this->_attributesRequired, 'small_image');
                        array_push($this->_attributesRequired, 'thumbnail');
                        break;
                    case 'sc_description':
                        array_push($this->_attributesRequired, 'description');
                        array_push($this->_attributesRequired, 'short_description');
                        array_push($this->_attributesRequired, 'manufacturer');
                        array_push($this->_attributesRequired, 'name');
                        array_push($this->_attributesRequired, 'sku');
                        break;
                    case 'sc_ean':
                        array_push($this->_attributesRequired, 'ean');
                        break;
                    case 'sc_url':
                        array_push($this->_attributesRequired, 'url_key');
                        array_push($this->_attributesRequired, 'url');
                        break;
                    default:
                        array_push($this->_attributesRequired, $info['property']);
                }
            }
        }
        $this->_attributesRequired = array_unique($this->_attributesRequired);
        return $result;
    }
    /**
     * Retrieve all attributes to use + attributes label
     * @param $typeId
     */
    private function extractAttributeList($typeId)
    {
        // BDD attribute list
        $attributesList = $this->attributeFactory->create()->getCollection()->addFieldToFilter('entity_type_id', ['eq' => $typeId]);
        // Build the list of required attributes that are available in the BDD
        $this->listOfAttributes = [];
        $this->listOfAttributesType = [];
        foreach ($attributesList as $key => $attr) {
            if (in_array($attr['attribute_code'], $this->_attributesRequired)) {
                array_push($this->listOfAttributes, $attr['attribute_code']);
                $this->listOfAttributesType[$attr['attribute_code']] = $attr['frontend_input'];
            }
        }
        // Add essential attributes to the list
        if (!in_array('special_price', $this->listOfAttributes)) {
            $this->listOfAttributes[] = 'special_price';
        }
        if (!in_array('special_from_date', $this->listOfAttributes)) {
            $this->listOfAttributes[] = 'special_from_date';
        }
        if (!in_array('special_to_date', $this->listOfAttributes)) {
            $this->listOfAttributes[] = 'special_to_date';
        }
        if (!in_array('price_type', $this->listOfAttributes)) {
            $this->listOfAttributes[] = 'price_type';
        }
        if (!in_array('price', $this->listOfAttributes)) {
            $this->listOfAttributes[] = 'price';
        }
        $this->listOfAttributes[] = 'tax_class_id';
        // Add attributes to filter
        foreach (json_decode((string) $this->params['attributes']) as $attributeFilter) {
            if (!in_array($attributeFilter->code, $this->listOfAttributes) && $attributeFilter->checked) {
                if (!in_array($attributeFilter->code, ["is_in_stock", "qty", "entity_id", "created_at", "updated_at"])) {
                    $this->listOfAttributes[] = $attributeFilter->code;
                }
            }
        }
        // Extract attributes labels
        $attributeLabels = $this->attributeOptionValueCollectionFactory->create();
        $attributeLabels->setStoreFilter($this->params['store_id'])->addOrder('option_id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC)->addOrder('tdv.store_id', \Magento\Framework\Data\Collection\AbstractDb::SORT_ORDER_ASC)->getData();
        $this->attributesLabelsList = [];
        foreach ($attributeLabels as $attributeLabel) {
            $this->attributesLabelsList[$attributeLabel['option_id']][$this->params['store_id']] = $attributeLabel['value'];
        }
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /* FILE SYSTEM UTILITIES                                                    */
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /**
     * Get full path of the data feed
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    protected function getFilePath()
    {
        return str_replace("//", "/", (string) ($this->storageHelper->getAbsoluteRootDir() . $this->getPath()));
    }
    /**
     * Get the full path of the generated file
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getPreparedFilename()
    {
        return $this->getFilePath() . $this->getName();
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /* TEMPLATING UTILITIES                                                     */
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /**
     * Enclose xml data into CDATA
     * @param string $productPattern
     * @param bool $enclose
     * @param bool $clean
     * @return string
     */
    public function xmlEncloseData($productPattern, $enclose = true, $clean = true)
    {
        $pattern = '/(<[^>!\\/]+>)([^<]*)(<\\/[^>]+>)/Us';
        $matches = [];
        preg_match_all($pattern, $productPattern, $matches);
        foreach (array_keys($matches[1]) as $key) {
            $tagContent = trim((string) $matches[2][$key]);
            if (empty($tagContent) && !is_numeric($tagContent) && $clean) {
                $productPattern = str_replace($matches[0][$key], '', $productPattern);
            } else {
                if ($enclose && strpos($tagContent, "<![CDATA[") === false) {
                    $productPattern = str_replace($matches[0][$key], $matches[1][$key] . '<![CDATA[' . $tagContent . ']]>' . $matches[3][$key], $productPattern);
                } else {
                    $productPattern = str_replace($matches[0][$key], $matches[1][$key] . $tagContent . $matches[3][$key], $productPattern);
                }
            }
        }
        $a = preg_split("/
/s", $productPattern);
        $o = '';
        foreach ($a as $line) {
            strlen(trim((string) $line)) > 0 ? $o .= $line . "
" : false;
        }
        return $o;
    }
    /**
     * Encode to uft8 or not
     * @param string $productPattern
     * @return string
     */
    public function encode($productPattern)
    {
        if ($this->_isPreview) {
            return $productPattern;
        } else {
            if ($this->params['encoding'] != 'UTF-8') {
                $productPattern = htmlentities($productPattern, ENT_NOQUOTES, 'UTF-8');
                $productPattern = html_entity_decode($productPattern, ENT_NOQUOTES, $this->params['encoding']);
            }
            return $productPattern;
        }
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /* BASIC UTILITIES                                                          */
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /**
     * Get store url
     * @param int $storeId
     * @return string
     */
    public function getStoreUrl($storeId = null)
    {
        if ($storeId == null) {
            $storeId = $this->getStoreId();
        }
        return $this->storeFactory->create()->load($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, $this->framework->getStoreConfig("web/secure/use_in_frontend", $storeId));
    }
    /**
     * Get store base url
     * @return strings
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreBaseUrl()
    {
        return $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB, $this->framework->getStoreConfig("web/secure/use_in_frontend", $this->params['store_id']));
    }
    /**
     *  In popup ?
     * @param boolean $d
     */
    public function setDisplay($d)
    {
        $this->_isPreview = $d;
    }
    /**
     * @return int
     */
    private function getEntityTypeId()
    {
        $typeId = -1;
        $resTypeId = $this->attributeTypeFactory->create()->getCollection()->addFieldToFilter('entity_type_code', ['eq' => 'catalog_product']);
        foreach ($resTypeId as $re) {
            $typeId = $re['entity_type_id'];
        }
        return $typeId;
    }
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /* DEBUG UTILITIES                                                        */
    /* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
    /**
     *
     */
    private function isLogEnabled()
    {
        return $this->framework->getStoreConfig("datafeedmanager/system/log") ? true : false;
    }
}