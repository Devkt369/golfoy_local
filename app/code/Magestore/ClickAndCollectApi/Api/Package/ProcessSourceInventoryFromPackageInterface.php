<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Package;

/**
 * Process source inventory when create and cancel packages
 *
 * @api
 */
interface ProcessSourceInventoryFromPackageInterface
{
    /**
     * Reserve Source Item Quantity From Package
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function reserveSourceItemQuantityFromPackage(
        \Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package
    ): void;

    /**
     * Return Source Item Quantity From Package
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function returnSourceItemQuantityFromPackage(
        \Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package
    ): void;
}
