<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver\Product;

use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magestore\Giftvoucher\Model\Product\Type\Giftvoucher;

/**
 * Post formatting for data in the giftcard template ids products
 */
class GiftCardTemplateIds implements ResolverInterface
{
    /**
     * @inheritDoc
     *
     * Add formatting for the giftcard template ids
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        /** @var Product $product */
        $product = $value['model'];

        $data = null;
        if ($product->getTypeId() === Giftvoucher::GIFT_CARD_TYPE) {
            if ($product->getGiftTemplateIds()) {
                return array_map('intval', explode(',', (string)$product->getGiftTemplateIds()));
            } else {
                return [];
            }
        }

        return $data;
    }
}
