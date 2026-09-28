<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Model;

use Magento\Framework\Module\Manager;

/**
 * Class for check Extensions enable status.
 * Modules with additional compatibilities
 */
class ModuleEnable
{
    public const AMASTY_FREE_GIFT_EXTENDED_VIEW = 'Amasty_FreeGiftExtendedView';

    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    public function isFreeGiftExtendedViewEnabled(): bool
    {
        return $this->moduleManager->isEnabled(self::AMASTY_FREE_GIFT_EXTENDED_VIEW);
    }
}
