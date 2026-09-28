<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Cart\BuyRequest;

use Magento\Framework\Stdlib\ArrayManager;

/**
 * Extract buy request elements require for gift card options
 */
class GiftCardDataProvider
{
    const GIFTCARD_OPTIONS = 'data/giftcard_options';

    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @param ArrayManager $arrayManager
     */
    public function __construct(
        ArrayManager $arrayManager
    ) {
        $this->arrayManager = $arrayManager;
    }

    /**
     * @inheritdoc
     */
    public function execute(array $cartItemData): array
    {
        return $this->arrayManager->get(self::GIFTCARD_OPTIONS, $cartItemData, []);
    }
}
