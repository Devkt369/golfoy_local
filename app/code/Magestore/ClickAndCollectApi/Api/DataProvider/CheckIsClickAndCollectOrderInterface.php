<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\DataProvider;

/**
 * Is click and collect order interface
 *
 * @api
 */
interface CheckIsClickAndCollectOrderInterface
{
    /**
     * Set is click and collect order
     *
     * @param bool $isClickAndCollectOrder
     * @return void
     */
    public function setIsClickAndCollectOrder(bool $isClickAndCollectOrder): void;

    /**
     * Check if is click and collect order
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsClickAndCollectOrder(): bool;
}
