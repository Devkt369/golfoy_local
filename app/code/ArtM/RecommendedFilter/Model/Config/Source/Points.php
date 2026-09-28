<?php
/**
 * Copyright © All rights reserved All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\RecommendedFilter\Model\Config\Source;

class Points implements \Magento\Framework\Option\ArrayInterface
{
    
    public function getOptionArray()
    {  
        $options =[
            '1'   => 'Enable',
            '2'   => 'Disable'
                ];
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