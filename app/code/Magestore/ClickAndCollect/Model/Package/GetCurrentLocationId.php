<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\Package;

use Magestore\ClickAndCollectApi\Api\Package\GetCurrentLocationIdInterface;

/**
 * Class Validate Package
 */
class GetCurrentLocationId implements GetCurrentLocationIdInterface
{
    /**
     * @var int
     */
    private $currentLocationId;

    /**
     * @inheritDoc
     */
    public function setCurrentLocationId(int $locationId): void
    {
        $this->currentLocationId = $locationId;
    }

    /**
     * @inheritDoc
     */
    public function getCurrentLocationId(): ?int
    {
        return $this->currentLocationId;
    }
}
