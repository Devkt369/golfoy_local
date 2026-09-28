<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api;

/**
 * Interface IsEnabledLogFlowInterface
 *
 * Check is enabled for the log flow
 */
interface IsEnabledLogFlowInterface
{
    /**
     * Execute service
     *
     * @param string $logFlow
     * @return int
     */
    public function execute(string $logFlow): int;
}
