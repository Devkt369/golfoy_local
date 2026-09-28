<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Package;

interface GetCurrentLocationIdInterface
{
    /**
     * Set Current Location Id
     *
     * @param int $locationId
     * @return void
     */
    public function setCurrentLocationId(int $locationId): void;

    /**
     * Get Current Location Id
     *
     * @return int|null
     */
    public function getCurrentLocationId(): ?int;
}
