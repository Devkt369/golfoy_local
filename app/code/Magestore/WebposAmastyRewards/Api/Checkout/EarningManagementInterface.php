<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\WebposAmastyRewards\Api\Checkout;

/**
 * Checkout - EarningManagementInterface
 */
interface EarningManagementInterface
{
    /**
     * Check Earning Point
     *
     * @param \Magestore\Webpos\Api\Data\Checkout\QuoteInterface $quote
     * @return \Magestore\WebposAmastyRewards\Api\Data\CheckEarningPointsResultInterface
     */
    public function checkEarningPoint(\Magestore\Webpos\Api\Data\Checkout\QuoteInterface $quote);
}
