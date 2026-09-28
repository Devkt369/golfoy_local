<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Package;

use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;

/**
 * Cancel pickup order package
 *
 * @api
 */
interface CancelPackageInterface
{
    const ACTION_TYPE = 'cancel';

    /**
     * Cancel package
     *
     * @param PackageInterface $package
     * @param bool $needUpdatePickupStatus
     * @return array
     */
    public function execute(PackageInterface $package, $needUpdatePickupStatus = false);
}
