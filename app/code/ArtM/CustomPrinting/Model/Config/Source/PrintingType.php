<?php
/**
 * Copyright © All rights reserved All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace ArtM\CustomPrinting\Model\Config\Source;

use \ArtM\Common\Helper\Data;
class PrintingType implements \Magento\Framework\Option\ArrayInterface
{
    protected $chelper;
    public function __construct(Data $chelper)
    {
            $this->chelper = $chelper;
    }
    public function getOptionArray()
    {  
        $options =['text'=> 'Text only','both'=> 'Text and Logo'];
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