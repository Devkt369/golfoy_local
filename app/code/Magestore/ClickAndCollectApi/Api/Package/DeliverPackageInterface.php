<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Package;

use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;

/**
 * Deliver pickup order package
 *
 * @api
 */
interface DeliverPackageInterface
{
    const ACTION_TYPE = 'deliver';

    /**
     * Deliver package
     *
     * @param PackageInterface $package
     * @param bool $needUpdatePickupStatus
     * @return array
     */
    public function execute(PackageInterface $package, $needUpdatePickupStatus = false);
}
