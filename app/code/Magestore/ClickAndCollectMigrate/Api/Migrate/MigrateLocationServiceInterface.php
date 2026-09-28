<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api\Migrate;

/**
 * Service - Migrate Location
 */
interface MigrateLocationServiceInterface
{
    /**
     * Migrate Location
     *
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function migrateLocation();

    /**
     * Create Location
     *
     * @param \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface $locationStoreMap
     * @throws \Magento\Framework\Exception\NotFoundException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Validation\ValidationException
     */
    public function createLocation(
        \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface $locationStoreMap
    );

    /**
     * Update Location
     *
     * @param \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface $locationStoreMap
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function updateLocation(
        \Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapInterface $locationStoreMap
    );
}
