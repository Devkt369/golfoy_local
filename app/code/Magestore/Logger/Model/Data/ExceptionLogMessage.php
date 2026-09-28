<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model\Data;

use Magestore\LoggerApi\Api\Data\ExceptionLogMessageInterface;

/**
 * Interface LogMessage
 *
 * Log Message Data Service
 */
class ExceptionLogMessage extends LogMessage implements ExceptionLogMessageInterface
{
    const LOGGER_FORMAT = 'exception';
}
