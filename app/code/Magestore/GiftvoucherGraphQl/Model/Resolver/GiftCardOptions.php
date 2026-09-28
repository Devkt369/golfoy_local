<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magestore\Giftvoucher\Model\Giftcard\Option as GiftCardOption;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote\Item;

/**
 * Recipient Email resolver for Giftcard Cart Item
 */
class GiftCardOptions implements ResolverInterface
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
            GiftCardOption::KEY_RECIPIENT_NAME,
            GiftCardOption::KEY_RECIPIENT_EMAIL,
            GiftCardOption::KEY_MESSAGE,
            GiftCardOption::KEY_DAY_TO_SEND,
            GiftCardOption::KEY_TIMEZONE_TO_SEND,
            GiftCardOption::KEY_RECIPIENT_ADDRESS,
            GiftCardOption::KEY_NOTIFY_SUCCESS,
            GiftCardOption::KEY_SEND_FRIEND,
            GiftCardOption::KEY_GIFTCARD_TEMPLATE_ID,
            GiftCardOption::KEY_CUSTOMER_NAME,
            GiftCardOption::KEY_GIFTCARD_TEMPLATE_IMAGE,
            GiftCardOption::KEY_GIFTCARD_CUSTOM_IMAGE,
            GiftCardOption::KEY_AMOUNT,
            GiftCardOption::KEY_GIFTCARD_AMOUNT
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
