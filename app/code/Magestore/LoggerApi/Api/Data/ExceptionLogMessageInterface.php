<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api\Data;

/**
 * Interface ExceptionLogMessageInterface
 *
 * Exception Log type interface data
 * @api
 */
interface ExceptionLogMessageInterface extends LogMessageInterface
{
    /**
     * Constants for keys of exception log
     */
    const EXCEPTION_LEVEL = 'exception_level';
    const EXCEPTION_MESSAGE = 'exception_message';
    const STACK_TRACE = 'stack_trace';
    const EXCEPTION_CODE = 'exception_code';
    const FILE = 'file';
    const LINE = 'line';
    /**#@-*/

    /**
     * Constants for exception level
     */
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';
    /**#@-*/
}
