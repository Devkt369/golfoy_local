<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Block\Adminhtml\System\Config;

use Amasty\Promo\Model\ModuleEnable;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class FreeGiftPosition extends Field
{
    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    public function __construct(
        Context $context,
        ModuleEnable $moduleEnable,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->moduleEnable = $moduleEnable;
    }

    protected function _getElementHtml(AbstractElement $element): string
    {
        if (!$this->moduleEnable->isFreeGiftExtendedViewEnabled()) {
            $element->setDisabled('disabled');
        }

        return parent::_getElementHtml($element);
    }
}
