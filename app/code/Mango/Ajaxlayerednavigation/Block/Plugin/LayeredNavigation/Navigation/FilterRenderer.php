<?php
namespace Mango\Ajaxlayerednavigation\Block\Plugin\LayeredNavigation\Navigation;

class FilterRenderer
{
    protected $jsonEncoder;
    protected $helper;
    protected $categoryTree;
    public function __construct(
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper,
        \Mango\Ajaxlayerednavigation\Block\LayeredNavigation\Navigation\Tree $tree
    ) {
        $this->jsonEncoder = $jsonEncoder;
        $this->helper = $helper;
        $this->categoryTree = $tree;
    }
    public function beforeRender(
        \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject,
        $filter
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return [ $filter];
        }       
        $subject->assign('filter', $filter);
        $subject->assign('ajax_helper', $this->helper); /* return mango helper */
        if ($filter instanceof \Magento\CatalogSearch\Model\Layer\Filter\Category) {
            $subject->setTemplate("Mango_Ajaxlayerednavigation::layer/category.phtml");
            /* assign categories tree */
            $categories_tree = $this->categoryTree->setFilter($filter)->getCategoriesTree();
            $subject->assign('categories_tree', $categories_tree);
        } else {
            $subject->assign('attribute', $filter->getAttributeModel());
            $_attribute_code = $filter->getAttributeModel()->getAttributeCode();
            
            if ($filter instanceof \Magento\CatalogSearch\Model\Layer\Filter\Price) {
                if ($_attribute_code === "price") {
                /* will set here the min/max price values... */
                $min_price = $filter->getMinPriceInt();
                $max_price = $filter->getMaxPriceInt();
                $selected_values = $filter->getFilterInterval();
                if (!$selected_values) {
                    $selected_values = [ $min_price, $max_price];
                }
                $config = [
                    'min' => $min_price,
                    'max' => $max_price,
                    'step' => 1,
                    'values' => $selected_values,
                    'price_slider_url' => $filter->getPriceSliderClearUrl()
                ];
                $json_config = $this->jsonEncoder->encode($config);
                $subject->assign('json_config', $json_config);
                $subject->assign('min_price', $selected_values[0]);
                $subject->assign('max_price', $selected_values[1]);
                /* show/hide textbox and search button */
                $subject->assign('hide_input', !$this->helper->getConfigValue('priceslider_search_fields', 'general'));
                $subject->setTemplate("Mango_Ajaxlayerednavigation::layer/price.phtml");
                } else { /*other numeric filters*/

                    /* not numeric filter nor 2 attributes filter... */
                    $numericSlider = $this->helper->sliderForNumericAttribute($_attribute_code);                  
                    if($numericSlider){
                        if (!$numericSlider) { 
                            return [$filter];
                        }
                        $_slider_config = $this->helper->sliderConfiguration($_attribute_code);
                        #print_r($_slider_config);
                        $subject->assign('units', $_slider_config['units']);
                        /* get configuration values for slider widgets */
                        $config = $this->getNumericSliderData($filter, $_slider_config);
			 $json_config = $this->jsonEncoder->encode($config);
                        $subject->assign('json_config', $json_config);
                        $subject->assign('min_value', $config['values'][0]);
                        $subject->assign('max_value', $config['values'][1]);
                        /* show/hide textbox and search button */
                        $subject->assign('hide_input', !$this->helper->getConfigValue('sliders_search_fields', 'general'));
                        $subject->setTemplate("Mango_Ajaxlayerednavigation::layer/sliders/decimal.phtml");
                    }else{
                        $subject->setTemplate("Mango_Ajaxlayerednavigation::layer/filter.phtml");
                    }
                }
            } elseif ($filter instanceof \Magento\CatalogSearch\Model\Layer\Filter\Attribute) {
                $_attribute_code = $filter->getAttributeModel()->getAttributeCode();
                if ($this->helper->sliderForAttribute($_attribute_code)) {
                    $subject->assign('encoder', $this->jsonEncoder);
                    $subject->setTemplate("Mango_Ajaxlayerednavigation::layer/sliders/sliders_attribute.phtml");
                } else {
                    #return [$filter];
                    /*  have to check if filter is active and if filter items are active */
                    $subject->setTemplate("Mango_Ajaxlayerednavigation::layer/filter.phtml");
                }
            } else {
                /*  have to check if filter is active and if filter items are active */
                $subject->setTemplate("Mango_Ajaxlayerednavigation::layer/filter.phtml");
            }
        }
        return [ $filter];
    }
    public function afterRender(
        \Magento\LayeredNavigation\Block\Navigation\FilterRenderer $subject,
        $result
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $result;
        }
        /* clear data after rendering... */
        $subject->assign('filter_is_active', false);
        $subject->assign('active_filter_items', false);
        return $result;
    }
    protected function getNumericSliderData(
        $filter,
        $_slider_config
    ) {
        /* will set here the min/max price values... */
        $_use_minimum_value = $this->helper->getConfigValue('sliders_use_minimum_value');
        $min_value = ($_use_minimum_value) ? $filter->getMinValueInt() : 0;
        $max_value = $filter->getMaxValueInt();
        $selected_values = $filter->getFilterInterval();
        $_step_value = $_slider_config['step_value'];
        $min_value = floor($min_value / $_step_value) * $_step_value;
        $max_value = ceil($max_value / $_step_value) * $_step_value;

        if (!$selected_values) {
            $selected_values = [$min_value, $max_value];
        }
        $config = [
            'min' => $min_value,
            'max' => $max_value,
            'step' => $_step_value,
            'values' => $selected_values/* ,
              'values_slider_url' => $filter->getNumericSliderClearUrl() */
        ];
        return $config;
    }

    protected function getClearParamsUrl($params) 
	{
        $urlParams = [
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => [
                'ajax_request' => null,
                'ajaxlist' => null,
                'reload_navigation' => null,
                '_' => null
            ],
            '_escape' => true,
        ];
        foreach ($params as $param) {
            $urlParams['_query'][$param] = null;
        }

        return $this->url->getUrl('*/*/*', $urlParams);
    }
}
