<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;
use Magestore\Giftvoucher\Model\Product\Type\Giftvoucher;

/**
 * Giftvoucher GraphQl - GiftCard Product Type Resolver
 */
class GiftCardProductTypeResolver implements TypeResolverInterface
{
    /**
     * @inheritDoc
     */
    public function resolveType(array $data) : string
    {
        if (isset($data['type_id']) && $data['type_id'] == Giftvoucher::GIFT_CARD_TYPE) {
            return 'GiftCardProduct';
        }
        return '';
    }
}
