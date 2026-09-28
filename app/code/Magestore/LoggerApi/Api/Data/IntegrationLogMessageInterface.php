<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api\Data;

/**
 * Interface IntegrationLogMessageInterface
 *
 * Integration Log type interface data
 * @api
 */
interface IntegrationLogMessageInterface extends LogMessageInterface
{
    /**
     * Constants for keys of data change log
     */
    const DATA_TYPE = 'data_type';
    const TITLE = 'title';
    const DATA_INTEGRATION = 'data_integration';
    const DATA_SYSTEM = 'data_system';
    /**#@-*/
}
