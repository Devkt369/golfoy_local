<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Package;

/**
 * Add items to pickup order package
 *
 * @api
 */
interface AddItemsToNewPackageInterface
{
    /**
     * Add item to the package
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package
     * @return void
     * @throws \Magento\Framework\Exception\AlreadyExistsException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(\Magestore\ClickAndCollectApi\Api\Data\PackageInterface $package);
}
