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
use Magento\Config\Model\Config\CommentInterface;
use Magento\Framework\View\Element\AbstractBlock;

class FreeGiftPositionComment extends AbstractBlock implements CommentInterface
{
    private const FREE_GIFT_EXTENDED_VIEW_URL = 'https://amasty.com/amcustomer/account/products/'
        . '?utm_source=extension&utm_medium=backend&utm_campaign=upgrade_subscribe';

    /**
     * @var ModuleEnable
     */
    private $moduleEnable;

    public function __construct(
        Context $context,
        ModuleEnable $moduleEnable,
        array $data = []
    ) {
        $this->moduleEnable = $moduleEnable;
        parent::__construct($context, $data);
    }

    /**
     * @param string $elementValue
     * @return string
     */
    public function getCommentText($elementValue): string
    {
        if ($this->moduleEnable->isFreeGiftExtendedViewEnabled()) {
            return '';
        }

        return (string)__('The \'Popup\' option is available and applied by default. Additionally, there is '
            . 'the \'Banner Slider\' option, which is accessible in the Pro version and as part of an active product '
            . 'subscription or support subscription. To upgrade and obtain functionality please follow the '
            . '<a href="%1">link</a>', self::FREE_GIFT_EXTENDED_VIEW_URL);
    }
}
