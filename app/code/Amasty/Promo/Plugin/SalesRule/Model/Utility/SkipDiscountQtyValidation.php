<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Plugin\SalesRule\Model\Utility;

use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule;
use Magento\SalesRule\Model\Utility;

class SkipDiscountQtyValidation
{
    /**
     * @var string[]
     */
    private $actionsToSkip = [];

    public function __construct($actionsToSkip)
    {
        $this->actionsToSkip = $actionsToSkip;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetItemQty(Utility $subject, float $result, AbstractItem $item, Rule $rule): float
    {
        if (in_array($rule->getSimpleAction(), $this->actionsToSkip)) {
            return (float)$item->getTotalQty();
        }

        return $result;
    }
}
