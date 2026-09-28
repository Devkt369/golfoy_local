<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectGraphQl\Model\Resolver\Summary;

use Magestore\ClickAndCollect\Model\Order\OrderPickupStatusResolverInterface;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * GetTotalPending field resolver, used for GraphQL request processing.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetTotalPending extends GetTotalOrderByStatusAbstract implements ResolverInterface
{
    const FILTER_STATUSES = [OrderPickupStatusResolverInterface::STATUS_PENDING];
}
