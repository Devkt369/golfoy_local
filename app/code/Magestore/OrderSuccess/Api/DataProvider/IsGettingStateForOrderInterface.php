<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\OrderSuccess\Api\DataProvider;

/**
 * Interface IsGettingStateForOrderInterface
 */
interface IsGettingStateForOrderInterface
{
    const IS_GETTING_STATE_FOR_ORDER = 'is_getting_state_for_order';

    /**
     * Set Is Getting State For Order
     *
     * @param int $isGettingStateForOrder
     */
    public function setIsGettingStateForOrder(int $isGettingStateForOrder): void;

    /**
     * Get Is Getting State For Order
     *
     * @return int
     */
    public function getIsGettingStateForOrder(): int;
}
