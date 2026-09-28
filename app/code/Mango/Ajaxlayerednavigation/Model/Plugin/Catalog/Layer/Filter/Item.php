<?php
/**
 * Filter item model
 *
 */
namespace Mango\Ajaxlayerednavigation\Model\Plugin\Catalog\Layer\Filter;

class Item
{
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
    protected $request;
    /**
     * Construct
     *
     * @param \Magento\Framework\UrlInterface $url
     * @param \Magento\Theme\Block\Html\Pager $htmlPagerBlock
     */
    public function __construct(
        \Magento\Framework\UrlInterface $url,
        \Magento\Theme\Block\Html\Pager $htmlPagerBlock,
        \Magento\Framework\App\Request\Http $request
    ) {
        $this->url = $url;
        $this->htmlPagerBlock = $htmlPagerBlock;
        $this->request = $request;
    }
    /**
     * Get filter item url
     *
     * @return string
     */
    public function aroundGetUrl(\Magento\Catalog\Model\Layer\Filter\Item $subject, \Closure $proceed)
    {
        $var = $subject->getFilter()->getRequestVar();
        $value = $subject->getValue();
        $selected_values = $this->request->getParam($var);
        $filter = [];
        if (preg_match('/^[0-9,-]+$/', $selected_values ?? '')) {
            $filter = explode(',', $selected_values);
        } elseif ((int) $selected_values > 0) {
            $filter[] = $selected_values;
        }
        //$value = $subject->getValue();
        if (is_array($value)) {
            $value = join("-", $value);
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
            $subject->getFilter()->getRequestVar() => $filter,
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
     * Get url for "clear" link
     *
     * @return false|string
     */
    public function aroundGetClearLinkUrl(\Magento\Catalog\Model\Layer\Filter\Item $subject, \Closure $proceed)
    {
        $urlParams = [
            '_current' => true,
            '_use_rewrite' => true,
            '_query' => [
                $subject->getFilter()->getRequestVar() => null,
                'ajax_request' => null,
                'ajaxlist' => null,
                'reload_navigation' => null,
                '_' => null
            ],
            '_escape' => true,
        ];
        return $this->url->getUrl('*/*/*', $urlParams);
    }
}
