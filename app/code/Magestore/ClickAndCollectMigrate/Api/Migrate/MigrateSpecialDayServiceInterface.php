<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api\Migrate;

/**
 * Service Interface - Migrate Special Day
 */
interface MigrateSpecialDayServiceInterface
{
    /**
     * Migrate special day
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function migrate();

    /**
     * Migrate From Store Pickup's Special Day
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function migrateFromSpecialDay();

    /**
     * Migrate From Store Pickup's Holiday
     *
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function migrateFromHoliday();
}
