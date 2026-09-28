<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Api\LogHandle;

/**
 * Interface ProcessLogExceptionInterface
 *
 * Handle log exception
 */
interface ProcessLogExceptionInterface
{
    /**
     * Handle log exception
     *
     * @param string $flow
     * @param string $businessFlow
     * @param string $message
     * @param string $level
     * @param \Exception $exception
     * @param array $otherFields
     * @return void
     */
    public function execute(
        string $flow,
        string $businessFlow,
        string $message,
        string $level,
        \Exception $exception,
        array $otherFields = []
    );
}
