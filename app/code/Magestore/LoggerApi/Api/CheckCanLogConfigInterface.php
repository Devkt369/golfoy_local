<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api;

/**
 * Interface CheckCanLogConfigInterface
 *
 * Check message can log or not in the config
 */
interface CheckCanLogConfigInterface
{
    /**
     * Execute service
     *
     * @param array $message
     * @return int
     */
    public function execute(array $message): int;
}
