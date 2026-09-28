<?php
namespace Mango\Ajaxlayerednavigation\Model\Config\Source;

class Urlhandler implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array[] = ['value' => 'hashChangeJs', 'label' => 'HashChange'];
        $array[] = ['value' => 'historyJs', 'label' => 'History.js'];
        return $array;
    }
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array[] = ['HashChange.js' => 'hashChangeJs'];
        $array[] = ['History.js' => 'historyJs'];
        return $array;
    }
}
