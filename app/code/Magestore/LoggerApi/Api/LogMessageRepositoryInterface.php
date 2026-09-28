<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api;

/**
 * Interface LogMessageRepositoryInterface
 *
 * Log Message Repository for write message
 * @api
 */
interface LogMessageRepositoryInterface
{
    /**
     * Save log to file
     *
     * @param \Magestore\LoggerApi\Api\Data\LogMessageInterface $logMessage
     */
    public function save(\Magestore\LoggerApi\Api\Data\LogMessageInterface $logMessage);
}
