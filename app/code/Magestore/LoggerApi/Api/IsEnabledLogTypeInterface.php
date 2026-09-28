<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api;

/**
 * Interface IsEnabledLogTypeInterface
 *
 * Check is enabled for the log type
 */
interface IsEnabledLogTypeInterface
{
    /**
     * Execute service
     *
     * @param string $logType
     * @return int
     */
    public function execute(string $logType): int;
}
