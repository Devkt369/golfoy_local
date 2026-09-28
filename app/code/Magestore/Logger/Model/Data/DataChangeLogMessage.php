<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model\Data;

use Magestore\LoggerApi\Api\Data\DataChangeLogMessageInterface;

/**
 * Interface LogMessage
 *
 * Log Message Data Service
 */
class DataChangeLogMessage extends LogMessage implements DataChangeLogMessageInterface
{
    const LOGGER_FORMAT = 'data_change';
}
