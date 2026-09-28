<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Resolve transaction type for schema
 */
class GiftVoucherTransactionTypeResolver implements TypeResolverInterface
{
    const GIFT_VOUCHER_TRANSACTION_TYPE_RESOLVER = "MSGiftVoucherTransaction";

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolveType(array $data) : string
    {
        return self::GIFT_VOUCHER_TRANSACTION_TYPE_RESOLVER;
    }
}
