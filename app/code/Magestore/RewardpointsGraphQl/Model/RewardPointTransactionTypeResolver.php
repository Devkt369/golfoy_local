<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model;

use Magento\Framework\GraphQl\Query\Resolver\TypeResolverInterface;

/**
 * Resolve transaction type for schema
 */
class RewardPointTransactionTypeResolver implements TypeResolverInterface
{
    const REWARD_TRANSACTION_TYPE_RESOLVER = "MSRewardPointTransaction";

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function resolveType(array $data) : string
    {
        return self::REWARD_TRANSACTION_TYPE_RESOLVER;
    }
}
