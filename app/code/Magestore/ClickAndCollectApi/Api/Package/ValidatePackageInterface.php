<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Package;

use Magestore\ClickAndCollectApi\Api\Data\PackageInterface;

/**
 * Validate pickup order package
 *
 * @api
 */
interface ValidatePackageInterface
{
    /**
     * Validate package
     *
     * @param PackageInterface $package
     * @return array
     */
    public function execute(PackageInterface $package);
}
