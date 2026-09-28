<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\DataProvider;

use Magestore\ClickAndCollectApi\Api\DataProvider\CheckIsClickAndCollectOrderInterface;

/**
 * Class IsClickAndCollectOrder
 *
 * Check is click and collect order
 */
class IsClickAndCollectOrder implements CheckIsClickAndCollectOrderInterface
{
    /**
     * @var bool
     */
    private $isClickAndCollectOrder = false;

    /**
     * Set is click and collect order
     *
     * @param bool $isClickAndCollectOrder
     * @return void
     */
    public function setIsClickAndCollectOrder(bool $isClickAndCollectOrder): void
    {
        $this->isClickAndCollectOrder = $isClickAndCollectOrder;
    }

    /**
     * Check if is click and collect order
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsClickAndCollectOrder(): bool
    {
        return $this->isClickAndCollectOrder;
    }
}
