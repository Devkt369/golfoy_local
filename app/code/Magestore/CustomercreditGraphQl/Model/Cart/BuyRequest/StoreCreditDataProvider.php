<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\CustomercreditGraphQl\Model\Cart\BuyRequest;

use Magento\Framework\Stdlib\ArrayManager;

/**
 * Extract buy request elements require for store credit options
 */
class StoreCreditDataProvider
{
    const STORECREDIT_OPTIONS = 'data/storecredit_options';

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
        return $this->arrayManager->get(self::STORECREDIT_OPTIONS, $cartItemData, []);
    }
}
