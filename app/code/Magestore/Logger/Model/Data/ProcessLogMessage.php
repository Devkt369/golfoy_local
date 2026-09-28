<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model\Data;

use Magestore\LoggerApi\Api\Data\ProcessLogMessageInterface;

/**
 * Integration LogMessage
 *
 * Log Message Data Service
 */
class ProcessLogMessage extends LogMessage implements ProcessLogMessageInterface
{
    const LOGGER_FORMAT = 'process';
}
