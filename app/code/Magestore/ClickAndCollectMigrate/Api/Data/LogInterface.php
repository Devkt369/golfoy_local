<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api\Data;

/**
 * Migrate log interface
 */
interface LogInterface
{
    const LOG_ID = "log_id";
    const LOGGED_AT = "logged_at";
    const MESSAGE = 'message';

    /**
     * Get log id
     *
     * @return int
     */
    public function getLogId(): int;

    /**
     * Set log id
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId);

    /**
     * Get logged at
     *
     * @return int
     */
    public function getLoggedAt();

    /**
     * Get message
     *
     * @return string|null
     */
    public function getMessage();

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message);
}
