<?php
namespace Mango\Ajaxlayerednavigation\Model\Plugin\Catalog\Layer\Filter;

class AbstractFilter
{
    protected $helper;
    
    public function __construct(
        \Mango\Ajaxlayerednavigation\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }
    
    
    public function aroundSetItems(\Magento\Catalog\Model\Layer\Filter\AbstractFilter $subject, \Closure $proceed, array $items)
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed($items);
        }
        if (count($items) == 0) {
            return $subject;
        } /* skip function if count items = 0 */
        $returnValue = $proceed();
        return $returnValue;
    }
    /**
     * Checks whether the option reduces the number of results
     *
     */
    public function aroundIsOptionReducesResults(\Magento\Catalog\Model\Layer\Filter\AbstractFilter $subject, \Closure $proceed, $optionCount, $totalSize)
    {
        /* check if extension is enabled always... */
        if (!$this->helper->isExtensionEnabled()) {
            return $proceed($optionCount, $totalSize);
        }
        return false;
    }
}
