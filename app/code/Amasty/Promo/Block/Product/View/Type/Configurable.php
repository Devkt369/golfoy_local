<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Block\Product\View\Type;

use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableView;

class Configurable extends ConfigurableView
{
    protected function _getAdditionalConfig(): array
    {
        return ['containerId' => "#ampromo_items_form-" . $this->getProduct()->getId()];
    }
}
