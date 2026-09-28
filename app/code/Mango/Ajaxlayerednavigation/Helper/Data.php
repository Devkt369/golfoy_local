<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mango\Ajaxlayerednavigation\Helper;

#use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Catalog data helper
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const  _AJAX_PARAMETER = 'ajaxlist';
    
    protected $config = [];
    protected $config_object;
    #protected $objectManager = null;
    protected $context = null;
    protected $extensionEnabled = null;
            
    protected $multiselectAndFilter = [];
    protected $attributeGroups = [];
    protected $numericSlidersConfiguration = false;
    protected $attributeSlidersConfiguration = false;
    protected $filterData = [];
    
    public function isExtensionEnabled()
    {
        return true; //$this->extensionEnabled;
    }
    
    public function getAjaxParameter()
    {
        return self::_AJAX_PARAMETER;
    }
    public function removeAjaxParameters(&$params)
    {
        $params[ self::_AJAX_PARAMETER ] = null;
        return $params;
    }
    
    public function isFilterExpanded($attribute_code)
    {
        if (!isset($this->config["collapse"])){
            $this->config["collapse"] = $this->getConfigValue("collapse");
        }
        
        #display any filter
        if(!$this->config["collapse"] ) {
            return true;
        }
        
        if (!isset($this->config["expand_filters"])) {
            $this->config["expand_filters"] = $this->_getConfigAsArray('expand_filters');
        }
        
        return in_array($attribute_code, $this->config["expand_filters"]);
    }
    
    public function isFilterHidden()
    {
        return;
        //hide_attributes
    }
    
    # check for multiselect attributes with "AND" condition
    public function isAndCondition($code)
    {
        $config = explode(",", trim((string) $this->scopeConfig->getValue(
            'ajaxlist/frontend/and_filter',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        )));
        return in_array($code, $config);
    }
    
    public function getHorizontalFilters()
    {
        if (!isset($this->config["horizontal_filters_array"])) {
            if ($this->getConfigValue("horizontal_mode")) {
                $this->config["horizontal_filters_array"] = $this->_getConfigAsArray('horizontal_filters');
            } else {
                $this->config["horizontal_filters_array"] = [];
            }
        }
        return $this->config["horizontal_filters_array"];
    }
    
    public function getFiltersToHide()
    {
        if (!isset($this->config["hide_attributes"])) {
            $this->config["hide_attributes"] = $this->_getConfigAsArray('hide_attributes');
        }
        return $this->config["hide_attributes"];
    }
    
    
    public function getTooltipsConfiguration()
    {
        if (!isset($this->config["tooltips_configuration"])) {
            ## load tooltips configuration
            $_tooltips = $this->getConfigValue("tooltips") ? explode("\n", trim($this->getConfigValue("tooltips_configuration"))) : false;
            $this->config["tooltips_configuration"] = [];
            if ($_tooltips) {
                foreach ($_tooltips as $_info) {
                    $_tooltip  = explode(",", trim($_info));
                    if (isset($_tooltip[0])&& isset($_tooltip[1])) {
                        $this->config["tooltips_configuration"][trim($_tooltip[0])] =  trim($_tooltip[1]);
                    }
                }
            }
        }
        return $this->config["tooltips_configuration"];
    }
    
    public function getTooltipCode($attribute_code)
    {
        $_conf = $this->getTooltipsConfiguration();
        if (isset($_conf[$attribute_code])) {
            return $_conf[$attribute_code];
        }
        return false;
    }
    protected function _getConfigAsArray($value)
    {
        $config = $this->_getConfigObject();
        $result = trim(
            (string) $config->getValue(
                'ajaxlist/frontend/' . $value,
                ScopeInterface::SCOPE_STORE
            )
        );
        $result = explode(",", $result);
        return $result;
    }
    
    public function getConfigValue($value, $section = 'frontend')
    {
 /* frontend or general */
        $config = $this->_getConfigObject();
        $result = trim(
            (string) $config->getValue(
                'ajaxlist/'.$section.'/' . $value,
                ScopeInterface::SCOPE_STORE
            )
        );
        return $result;
    }
    
    public function getConfigSection($section = 'frontend')
    {
 /* frontend or general */
        $config = $this->_getConfigObject();
        $result = $config->getValue(
            'ajaxlist/'.$section,
            ScopeInterface::SCOPE_STORE
        );
        return $result;
    }
    
    
    protected function _getConfigObject()
    {
        if (!$this->config_object) {
            $this->config_object = $this->scopeConfig;//context->getScopeConfig() ;#$this->objectManager->get('Magento\Framework\App\Config');
        }
        return $this->config_object;
    }
    
    public function getRequest()
    {
        return $this->_request;
    }
    
    # use to store data of multiselect filters with AND condition
    public function setMultiselectAndFilter($ids)
    {
        $this->multiselectAndFilter = $ids;
    }
    
    public function getMultiselectAndFilter()
    {
        return $this->multiselectAndFilter;
    }
    
    
    public function getGroupedFiltersConfiguration()
    {
        if (!isset($this->config["grouped_filters_configuration"])) {
            ## load tooltips configuration
            $_config = $this->getConfigValue("grouped_filters") ? explode("\n", trim($this->getConfigValue("grouped_filters"))) : false;
            $this->config["grouped_filters_configuration"] = [];
            if ($_config) {
                foreach ($_config as $_info) {
                    $_data  = explode(":", trim($_info));
                    if (isset($_data[0])&& isset($_data[1])) {
                        $attributes =   explode(",",trim($_data[1]));
                        $this->config["grouped_filters_configuration"][trim($_data[0])] = $attributes;
                        foreach($attributes as $att){
                            $this->attributeGroups[$att] = trim($_data[0]);
                        }
                    }
                }
            }
        }
        return $this->config["grouped_filters_configuration"];
    }
    
    public function getAttributeGroupBlock($attributeCode)
    {
        $this->getGroupedFiltersConfiguration();
        return ( isset($this->attributeGroups[$attributeCode])? $this->attributeGroups[$attributeCode]: false );
    }
    
    
    /* multiple sliders functions */
    
    public function getSlidersConfiguration()
    {
        
        if($this->numericSlidersConfiguration===false){
            $config = $this->_getConfigObject();
            $_config = $config->getValue('ajaxlist/general/sliders_configuration', ScopeInterface::SCOPE_STORE);
            $_config = preg_split('#\n(?!s)#', $_config);
            $this->numericSlidersConfiguration = [];
            foreach ($_config as $line) {
                $_conf =[];// new \Magento\Framework\DataObject();
                $info = explode(';', $line);
                if (!isset($info[0])) {
                    continue;
                }
                if (isset($info[1])) {
                    $_conf['step_value'] = ((int)$info[1])? (int)$info[1] : 1;
                }
                if (isset($info[2])) {
                    $_conf['units'] = $info[2];
                }
                /*if (!$_conf->getStepValue(1))
                    $_conf->setStepValue(1);*/
                $this->numericSlidersConfiguration[$info[0]] = $_conf;
            }
        }
        
        return $this->numericSlidersConfiguration;
    }

    public function changeUnit(&$data, $product) 
    {
        $_configuration = $this->getSlidersConfiguration();
        foreach ($data as $_code => $_info) {
            if (isset($_configuration[$_code])) {
                $data[$_code]['value'] = round($product->getData($_code), 1) . $_configuration[$_code]->getUnits();
            }
        }
    }
    
    public function sliderForNumericAttribute($attribute_code)
    {
        
        $_configuration = $this->getSlidersConfiguration();
        return isset( $_configuration[$attribute_code] );
    }
    
    public function sliderForAttribute($attribute_code)
    {
        
        $_config = $this->getAttributeSlidersConfiguration();
        return in_array($attribute_code, $_config);
    }
    public function getAttributeSlidersConfiguration()
    {
        
        if( $this->attributeSlidersConfiguration===false){
            $config = $this->_getConfigObject();
            $_config = $config->getValue('ajaxlist/general/attribute_sliders_configuration', ScopeInterface::SCOPE_STORE);
            $this->attributeSlidersConfiguration = explode(",", $_config ?? 'empty');
        }
        return $this->attributeSlidersConfiguration;
    }
    
    
    public function sliderConfiguration($attribute_code)
    {
        
        $_configuration = $this->getSlidersConfiguration();
        if(isset( $_configuration[$attribute_code] )){
            return $_configuration[$attribute_code];
	}
        return false; 
    }
    
    
}
