<?php
/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_RewardPoints
 * @module      RewardPoints
 * @author        Magestore Developer
 *
 * @copyright   Copyright (c) 2016 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 *
 */

namespace Magestore\Rewardpoints\Api;

/**
 * Interface RateRepositoryInterface
 */
interface GetRateByCustomerInterface
{
    /**
     * Find Rate by customerId and direction
     *
     * @param int $direction
     * @param int $customerGroupId
     * @param int $websiteId
     * @return array
     */
    public function execute($direction, $customerGroupId, $websiteId): array;
}
