<?php
/**
 * Copyright © All rights reserved All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\CustomPrinting\Model\Config\Source;

use \ArtM\Common\Helper\Data;
class CustomPrinting implements \Magento\Framework\Option\ArrayInterface
{
    protected $chelper;
    public function __construct(Data $chelper)
    {
            $this->chelper = $chelper;
    }
    public function getOptionArray()
    {  
        $options = [];
        $options[0] = "--please select--";
        $attributeSetList = $this->chelper->listAttributeSet();
        if ($attributeSetList) {
            foreach ($attributeSetList->getItems() as $list) {
                $options[$list->getAttributeSetId()] = $list->getAttributeSetName();
            }
        }
        return $options;
    }

    public function getAllOptions()
    {
        $res = $this->getOptions();
        array_unshift($res, ['value' => '', 'label' => '']);
        return $res;
    }

    public function getOptions()
    {
        $res = [];
        foreach ($this->getOptionArray() as $index => $value) {
            $res[] = ['value' => $index, 'label' => $value];
        }
        return $res;
    }

    public function toOptionArray()
    {
        return $this->getOptions();
    }  
}