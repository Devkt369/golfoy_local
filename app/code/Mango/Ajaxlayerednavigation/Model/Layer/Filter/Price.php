<?php
namespace Mango\Ajaxlayerednavigation\Model\Layer\Filter;

/**
 * Layer price filter based on Search API
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Price extends \Magento\CatalogSearch\Model\Layer\Filter\Price
{
    /**
     * @var \Magento\Catalog\Model\Layer\Filter\DataProvider\Price
     */
    private $dataProvider;
    /**
     * @var \Magento\Framework\Pricing\PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price
     */
    // private $resource;
    protected $isFilterActive;
    protected $minPriceInt;
    protected $maxPriceInt;
    protected $url;
    protected $helper;
    protected $dataProvideFactory;
    protected $minValueInt;
    protected $maxValueInt;
    protected $interval;
    /**
     * @var \Magento\Customer\Model\Session
     */
    // private $customerSession;
    /**
     * @var \Magento\Framework\Search\Dynamic\Algorithm
     */
    // private $priceAlgorithm;
    /**
     * @param \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Layer $layer
     * @param \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder
     * @param \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory
     * @param \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function __construct(
        \Magento\Catalog\Model\Layer\Filter\ItemFactory $filterItemFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Layer $layer,
        \Magento\Catalog\Model\Layer\Filter\Item\DataBuilder $itemDataBuilder,
        \Magento\Catalog\Model\ResourceModel\Layer\Filter\Price $resource,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Search\Dynamic\Algorithm $priceAlgorithm,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\Catalog\Model\Layer\Filter\Dynamic\AlgorithmFactory $algorithmFactory,
        \Magento\Catalog\Model\Layer\Filter\DataProvider\PriceFactory $dataProviderFactory,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Magento\Framework\UrlInterface $url,
        array $data = []
    ) {
        parent::__construct(
            $filterItemFactory,
            $storeManager,
            $layer,
            $itemDataBuilder,
            $resource,
            $customerSession,
            $priceAlgorithm,
            $priceCurrency,
            $algorithmFactory,
            $dataProviderFactory,
            $data
        );
        $this->helper = $helper;
        $this->priceCurrency = $priceCurrency;
        $this->dataProviderFactory = $dataProviderFactory;
        $this->url = $url;
    }
    /**
     * Apply price range filter
     *
     * @param \Magento\Framework\App\RequestInterface $request
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function apply(\Magento\Framework\App\RequestInterface $request)
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return parent::apply($request);
        }

        if ($this->getRequestVar() == "price") {
            return $this->applyPrice($request);
        } else {
            return $this->applyDecimalFilter($request);
        }
    }

    public function applyPrice(\Magento\Framework\App\RequestInterface $request)
    {
        /**
         * Filter must be string: $fromPrice-$toPrice
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter || is_array($filter)) {
            return $this;
        }
        $filterParams = explode(',', $filter);
        $filter = $this->getDataProvider()->validateFilter($filterParams[0]);
        if (!$filter) {
            return $this;
        }
        $this->getDataProvider()->setInterval($filter);
        $priorFilters = $this->getDataProvider()->getPriorFilters($filterParams);
        if ($priorFilters) {
            $this->getDataProvider()->setPriorIntervals($priorFilters);
        }
        list($from, $to) = $filter;
        $this->getLayer()->getProductCollection()->addFieldToFilter(
            'price',
            ['from' => $from, 'to' => $to ] //empty($to) || $from == $to ? $to : $to - self::PRICE_DELTA]
        );
        $this->getLayer()->getState()->addFilter(
            $this->_createItem($this->_renderRangeLabel(empty($from) ? 0 : $from, $to), $filter)
        );
        $this->isFilterActive = true;
        return $this;
    }



        /**
         * Apply decimal range filter
         *
         * @param \Magento\Framework\App\RequestInterface $request
         * @return $this
         * @SuppressWarnings(PHPMD.NPathComplexity)
         */
    public function applyDecimalFilter(\Magento\Framework\App\RequestInterface $request)
    {
        /**
         * Filter must be string: $fromValue-$toValue
         */
        $filter = $request->getParam($this->getRequestVar());
        if (!$filter || is_array($filter)) {
            return $this;
        }
        #$filterParams = explode('-', $filter);
        $filter = $this->validateFilter($filter);
        if (!$filter) {
            return $this;
        }
        #$priorFilters = $this->dataProvider->getPriorFilters($filterParams);
        #if ($priorFilters) {
        #    $this->dataProvider->setPriorIntervals($priorFilters);
        #}
        list($from, $to) = $filter;
        $this->interval = $filter;
        $this->getLayer()->getProductCollection()->addFieldToFilter(
            $this->getRequestVar(),
            ['from' => $from, 'to' => $to] //empty($to) || $from == $to ? $to : $to - self::VALUE_DELTA]
        );
        $this->getLayer()->getState()->addFilter(
            $this->_createItem($this->renderRangeLabel(empty($from) ? 0 : $from, $to), $filter)
        );
        $this->isFilterActive = true;
        return $this;
    }

    public function validateFilter($filter)
    {
        $filter = explode('-', $filter);
        if (count($filter) != 2) {
            return false;
        }
        foreach ($filter as $v) {
            if ($v !== '' && $v !== '0' && (double) $v <= 0 || is_infinite((double) $v)) {
                return false;
            }
        }
        return $filter;
    }
        /**
         * Prepare text of range label
         *
         * @param float|string $fromValue
         * @param float|string $toValue
         * @return float|\Magento\Framework\Phrase
         */
    public function renderRangeLabel($fromValue, $toValue)
    {
        $attribute = $this->getAttributeModel();
        $_attribute_code = $attribute->getAttributeCode();
        #if($this->helper->isFirstInSlider($_attribute_code)) return false;
        $_slider_config = $this->helper->sliderConfiguration($_attribute_code);
        if ($_slider_config) {# numeric sliders
            $unit = $_slider_config['units'];
            if ($toValue === '') {
                return __('%1 and above', $fromValue . $unit);
            } else {
                return __('%1 - %2', $fromValue . $unit, $toValue . $unit);
            }
        } else {
            $formattedFromValue = $fromValue; //$this->priceCurrency->format($fromValue);
            if ($toValue === '') {
                return __('%1 and above', $formattedFromValue);
            } /* elseif ($fromValue == $toValue && $this->dataProvider->getOnePriceIntervalValue()) {
              return $formattedFromValue;
              } */ else {
                return __('%1 - %2', $formattedFromValue, $toValue);
}
        }
    }

    public function getDataProvider()
    {
        if (!$this->dataProvider) {
            $this->dataProvider = $this->dataProviderFactory->create(['layer' => $this->getLayer()]);
        }
        return $this->dataProvider;
    }
    /**
     * Prepare text of range label
     *
     * @param float|string $fromPrice
     * @param float|string $toPrice
     * @return float|\Magento\Framework\Phrase
     */
    protected function _renderRangeLabel(
        $fromPrice,
        $toPrice,
        $isLast = false
    ) {
        $formattedFromPrice = $this->priceCurrency->format($fromPrice);
        if ($toPrice === '') {
            return __('%1 and above', $formattedFromPrice);
        } elseif ($fromPrice == $toPrice && $this->getDataProvider()->getOnePriceIntervalValue()) {
            return $formattedFromPrice;
        } else {
            return __('%1 - %2', $formattedFromPrice, $this->priceCurrency->format($toPrice));
        }
    }
    /**
     * Get data array for building attribute filter items
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getItemsData()
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return parent::_getItemsData();
        }
        $attribute = $this->getAttributeModel();
        if ($attribute->getAttributeCode() == "price") {
            return $this->_getItemsDataPrice($attribute);
        } else {
            return $this->_getItemsDataDecimal($attribute);
        }
    }
    protected function _getItemsDataPrice($attribute)
    {
        /* check if extension is enabled always... */
        $attribute = $this->getAttributeModel();
        $this->requestVar = $attribute->getAttributeCode();
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $facets = $productCollection->getFacetedData($attribute->getAttributeCode());
        if ($facets && isset($facets['min']) && isset($facets['min']['price'])) {
            $this->minPriceInt = $facets['min']['price'];
        } else {
            $this->minPriceInt = 0;
        }
        if ($facets && isset($facets['max']) && isset($facets['max']['price'])) {
            $this->maxPriceInt = $facets['max']['price'];
        } else {
            $this->maxPriceInt = 0;
        }

        $facets['count']['label'] = 'price';
        return [$facets['count']];

        #$data = [];
        #$data[] = 1;
        #return $data;
    }
    /**
     * Get data array for building attribute filter items
     *
     * @return array
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _getItemsDataDecimal($attribute)
    {
        /* check if extension is enabled always... */
        #$attribute = $this->getAttributeModel();
        $this->requestVar = $attribute->getAttributeCode();
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $productCollection */
        $productCollection = $this->getLayer()->getProductCollection();
        $facets = $productCollection->getFacetedData($attribute->getAttributeCode());
        #print_r($facets);
        #exit;
        $this->minValueInt = $facets['min'][$this->requestVar];
        $this->maxValueInt = $facets['max'][$this->requestVar];
        #
        #
        #$data = [];
        #$data[] = 1;
        #return $data;



        $facets['count']['label'] = 'price';
        return [$facets['count']];
    }
    /**
     * @param string $key
     * @param int $count
     * @return array
     */
    private function prepareData(
        $key,
        $count
    ) {
        list($from, $to) = explode('_', $key);
        if ($from == '*') {
            $from = $this->getFrom($to);
        }
        if ($to == '*') {
            $to = $this->getTo($to);
        }
        $label = $this->_renderRangeLabel(
            empty($from) ? 0 : $from * $this->getCurrencyRate(),
            empty($to) ? $to : $to * $this->getCurrencyRate()
        );
        $value = $from . '-' . $to . $this->getDataProvider()->getAdditionalRequestData();
        $data = [
            'label' => $label,
            'value' => $value,
            'count' => $count,
            'from' => $from,
            'to' => $to,
        ];
        return $data;
    }
    public function isFilterActive()
    {
        return $this->isFilterActive;
    }
    public function getFilterInterval()
    {
        $attribute = $this->getAttributeModel();
        if ($attribute->getAttributeCode() == "price") {
            $interval = $this->getDataProvider()->getInterval();
            if (count($interval) == 2) {
                return $interval;
            }
        } else {
            return $this->interval;
        }
        return false;
    }
    public function getMinPriceInt()
    {
        return $this->minPriceInt;
    }
    public function getMaxPriceInt()
    {
        return $this->maxPriceInt;
    }
    /* For numeric  */
    public function getMinValueInt()
    {
        return $this->minValueInt;
    }
    public function getMaxValueInt()
    {
        return $this->maxValueInt;
    }
    /**
     * Retrieve Clear Filters URL
     *
     * @return string
     */
    public function getPriceSliderClearUrl()
    {
        $urlParams = [
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => [
                $this->getRequestVar() => null,
                'ajax_request' => null,
                'ajaxlist' => null,
                'reload_navigation' => null,
                '_' => null
            ],
            '_escape' => true,
        ];
        return $this->url->getUrl('*/*/*', $urlParams);
    }
    public function getCurrencySymbol()
    {
        return $this->priceCurrency->getCurrencySymbol();
    }
    public function getCode()
    {
        $attribute = $this->getAttributeModel();
        return $attribute->getAttributeCode();
    }
}
