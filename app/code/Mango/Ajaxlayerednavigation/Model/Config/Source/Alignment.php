<?php
namespace Mango\Ajaxlayerednavigation\Model\Config\Source;

class Alignment implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        $array[] = ['value' => 'left', 'label' => 'Left'];
        $array[] = ['value' => 'center', 'label' => 'Center'];
        $array[] = ['value' => 'right', 'label' => 'Right'];
        return $array;
    }
    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $array[] = ['Left' => 'left'];
        $array[] = ['Center' => 'center'];
        $array[] = ['Right' => 'right'];
        return $array;
    }
}
