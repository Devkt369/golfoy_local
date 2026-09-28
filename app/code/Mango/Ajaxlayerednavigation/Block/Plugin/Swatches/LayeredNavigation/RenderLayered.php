<?php
namespace Mango\Ajaxlayerednavigation\Block\Plugin\Swatches\LayeredNavigation;

class RenderLayered
{
    /**
     * Core store config
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    #protected $scopeConfig;
    /**
     * Url
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $url;
    /**
     * Html pager block
     *
     * @var \Magento\Theme\Block\Html\Pager
     */
    protected $htmlPagerBlock;
    #protected $request;
    protected $helper;
    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Mango\Ajaxlayerednavigation\Helper\Data $helper
    ) {
        $this->url = $url;
        $this->htmlPagerBlock = $htmlPagerBlock;
        $this->helper = $helper;
    }
    public function aroundGetTemplate(
        \Magento\Swatches\Block\LayeredNavigation\RenderLayered $subject,
        \Closure $proceed
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed();
        }
        return "Mango_Ajaxlayerednavigation::layer/swatches-renderer.phtml";
    }
    /**
     * @param string $attributeCode
     * @param int $optionId
     * @return string
     */
    public function aroundBuildUrl(
        \Magento\Swatches\Block\LayeredNavigation\RenderLayered $subject,
        \Closure $proceed,
        $attributeCode,
        $optionId
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed($attributeCode, $optionId);
        }
        $var = $attributeCode;
        $value = $optionId;
        $selected_values = $this->helper->getRequest()->getParam($var);
        $filter = [];
        if (preg_match('/^[0-9,-]+$/', (string) $selected_values)) {
            $filter = explode(',', $selected_values);
        } elseif ((int) $selected_values > 0) {
            $filter[] = $selected_values;
        }
        if (is_array($value)) {
            $value = join(",", $value);
        } /* for the price filter.. */
        if (in_array($value, $filter)) {
            array_splice($filter, array_search($value, $filter), 1);
        } else {
            $filter[] = $value;
        }
        $filter = array_unique($filter);
        $filter = join(",", $filter);
        if (!$filter) {
            $filter = null;
        }
        $query = [
            $attributeCode => $filter,
            // exclude current page from urls
            $this->htmlPagerBlock->getPageVarName() => null,
            // exclude ajax parameters
            'ajax_request' => null,
            'ajaxlist' => null,
            'reload_navigation' => null,
            '_' => null
        ];
        return $this->url->getUrl('*/*/*', ['_current' => true, '_use_rewrite' => true, '_query' => $query]);
    }
    /**
     * @return array
     */
    public function afterGetSwatchData(
        \Magento\Swatches\Block\LayeredNavigation\RenderLayered $subject,
        $data
    ) {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $data;
        }
        /*  have to check if filter is active and if filter items are active */
        $attribute = $data["attribute_code"];
        $active_values = $this->_getActiveValues($attribute, $data['options']);
        /* to see if filter is active, check url parameters */
        $data['filter_is_active'] = (count($active_values) > 0);
        $data['active_filter_items'] = $active_values;
        return $data;
    }
    protected function _getActiveValues(
        $attribute,
        $items
    ) {
        /* to do : use only one function, try using a helper.. */
        $selected_values = $this->helper->getRequest()->getParam($attribute);
        if (!$selected_values) {
            return [];
        }
        $filter = [];
        if (preg_match('/^[0-9,-]+$/', $selected_values)) {/* for the price filter, added - */
            $filter = explode(',', $selected_values);
        } elseif ((int) $selected_values > 0) {
            $filter[] = $selected_values;
        }
        $values = [];
        foreach ($items as $value => $item) {
            if (in_array($value, $filter)) {
                $values[] = $value;
            }
        }
        if (!empty($values)) {
            return $values;
        } else {
            return [];
        }
    }
}
