<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Tax;

use Magento\Tax\Model\Calculation\Rate;
use Magestore\Webpos\Api\Data\Tax\TaxRateInterface;

/**
 * Class TaxRate
 *
 * Get tax rate
 */
class TaxRate extends Rate implements TaxRateInterface
{
    /**
     * @inheritdoc
     */
    public function getTitles()
    {
        if ($this->getData(self::KEY_TITLES)) {
            return $this->getData(self::KEY_TITLES);
        } else {
            return [];
        }
    }
}
