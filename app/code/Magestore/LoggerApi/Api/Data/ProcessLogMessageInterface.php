<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api\Data;

/**
 * Interface ProcessLogMessageInterface
 *
 * Integration Log type interface data
 * @api
 */
interface ProcessLogMessageInterface extends LogMessageInterface
{
    /**
     * Constants for keys of process log
     */
    const PROCESS_TYPE = 'process_type';
    const PROCESS_NAME = 'process_name';
    const PROCESS_INPUT = 'process_input';
    const DEBUG_PRINT_BACK_TRACE = 'debug_print_backtrace';
    /**#@-*/

    /**
     * Process types
     */
    const PROCESS_TYPE_SYSTEM = 'system';
    const PROCESS_TYPE_BUSINESS = 'business';
    /**#@-*/
}
