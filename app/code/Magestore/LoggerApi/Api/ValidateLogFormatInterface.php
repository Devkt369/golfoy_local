<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api;

/**
 * Interface ValidateLogFormatInterface
 *
 * Validate log format interface
 * @api
 */
interface ValidateLogFormatInterface
{
    /**
     * Validate message with type
     *
     * @param array $message
     * @param string $type
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(array $message, string $type): bool;
}
