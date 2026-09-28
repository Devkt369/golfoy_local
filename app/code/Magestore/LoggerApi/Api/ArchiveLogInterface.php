<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\LoggerApi\Api;

/**
 * Interface ArchiveLogByFrequencyInterface
 *
 * Archive Log by frequency
 */
interface ArchiveLogInterface
{
    /**
     * Execute the archive
     *
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function execute(): void;
}
