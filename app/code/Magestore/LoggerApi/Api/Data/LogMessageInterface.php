<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api\Data;

/**
 * Interface LogTypeInterface
 *
 * Log type interface data
 * @api
 */
interface LogMessageInterface
{
    /**
     * Constants for keys of log
     */
    const LOG_TYPE = 'log_type';
    const LOG_TIME = 'log_time';
    const APP = 'app';
    const SOURCE = 'source';
    const LOG_FLOW = 'log_flow';
    const BUSINESS_FLOW = 'business_flow';
    const USER_TYPE = 'user_type';
    const USER_EMAIL = 'user_email';
    const USER_NAME = 'user_name';
    const LOG_MESSAGE = 'log_message';
    const ADDITIONAL_INFO = 'additional_info';
    /**#@-*/

    /**
     * Prepare data before set data
     *
     * @param array $data
     * @return array
     */
    public function prepareData(array $data): array;

    /**
     * Validate message is satisfy type or not
     *
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validate();

    /**
     * Write Message To File
     *
     * @return void
     */
    public function writeToFile();

    /**
     * Log invalid record
     *
     * @param string $message
     * @return void
     */
    public function logInvalid(string $message);

    /**
     * Reformat order by structure
     *
     * @return $this
     */
    public function reformatByOrder();
}
