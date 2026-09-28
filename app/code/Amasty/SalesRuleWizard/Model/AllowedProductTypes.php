<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Sales Rules Wizard for Magento 2 (System)
 */

namespace Amasty\SalesRuleWizard\Model;

use Amasty\Promo\Helper\Data;

class AllowedProductTypes
{
    /**
     * @var Data
     */
    private Data $dataHelper;

    public function __construct(Data $dataHelper)
    {
        $this->dataHelper = $dataHelper;
    }

    /**
     * @return string[]
     */
    public function get(): array
    {
        return $this->dataHelper->getAllowedProductTypes();
    }
}
