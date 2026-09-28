<?php
/**
 * Copyright © 2019 Wyomind. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Wyomind\DataFeedManager\Model;

/**
 * Class Functions
 * @package Wyomind\DataFeedManager\Model
 */
class Functions extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Wyomind\DataFeedManager\Model\ResourceModel\Functions');
    }
}
