<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Logger\Model\Data;

use Magestore\LoggerApi\Api\Data\IntegrationLogMessageInterface;

/**
 * Integration LogMessage
 *
 * Log Message Data Service
 */
class IntegrationLogMessage extends LogMessage implements IntegrationLogMessageInterface
{
    const LOGGER_FORMAT = 'integration';
}
