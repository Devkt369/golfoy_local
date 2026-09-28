<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api\Data;

/**
 * Interface DataChangeLogMessageInterface
 *
 * Data Change Log type interface data
 * @api
 */
interface DataChangeLogMessageInterface extends LogMessageInterface
{
    /**
     * Constants for keys of data change log
     */
    const DATA_TYPE = 'data_type';
    const DATA_BEFORE = 'data_before';
    const DATA_AFTER = 'data_after';
    /**#@-*/
}
