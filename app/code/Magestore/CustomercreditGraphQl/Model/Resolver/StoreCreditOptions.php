<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\CustomercreditGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Recipient Email resolver for Store credit Cart Item
 */
class StoreCreditOptions implements ResolverInterface
{
    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!(($value['model'] ?? null) instanceof CartItemInterface)) {
            throw new LocalizedException(__('"model" value should be specified'));
        }
        /** @var Item $cartItem */
        $cartItem = $value['model'];

        $giftCardOptions = [
            'customer_name',
            'send_friend',
            'recipient_name',
            'recipient_email',
            'message',
            'amount',
            'credit_price_amount'
        ];

        $result = [];
        foreach ($giftCardOptions as $key) {
            if ($cartItem->getOptionByCode($key)) {
                $result[$key] = $cartItem->getOptionByCode($key)->getValue();
            }
        }
        return $result;
    }
}
