<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Plugin\Klarna\Orderlines\Model\Items;

use Amasty\Promo\Model\Config;
use Amasty\Promo\Model\Config\Source\GiftRepresentationMode;
use Klarna\Orderlines\Model\Container\DataHolder;
use Klarna\Orderlines\Model\Container\Parameter;
use Klarna\Orderlines\Model\Items\Items;
use Magento\Quote\Api\Data\CartInterface;

/**
 * We should unset base original price
 * if the option "Represent Free Gift" is set to Product with $0 Price and the product  is freegift
 * @see \Klarna\Orderlines\Model\Calculator\Item::getProcessedItem
 * @see \Amasty\Promo\Model\Config::getGiftRepresentationMode
 */
class UnsetBaseOriginalPrice
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function beforeCollectPrePurchase(
        Items $subject,
        Parameter $parameter,
        DataHolder $dataHolder,
        CartInterface $quote
    ): ?array {
        if ($this->config->getGiftRepresentationMode() !== GiftRepresentationMode::SHOW_ZERO_PRICE) {
            return null;
        }

        foreach ($dataHolder->getItems() as $item) {
            if ($item['price'] == 0 && isset($item['ampromo_rule_id'])) {
                $item['base_original_price'] = 0;
            }
        }

        return [$parameter, $dataHolder, $quote];
    }
}
