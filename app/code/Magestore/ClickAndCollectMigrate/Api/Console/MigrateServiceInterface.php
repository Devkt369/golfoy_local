<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api\Console;

/**
 * Click and collect - Migrate service interface
 */
interface MigrateServiceInterface
{
    /**
     * Migrate data from Store Pickup to Click And Collect
     *
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param bool $manual
     * @param bool $ignoreUnmappedStore
     * @param bool $force
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function migrate(
        \Symfony\Component\Console\Output\OutputInterface $output,
        bool $manual = false,
        bool $ignoreUnmappedStore = false,
        bool $force = false
    );
}
