<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel\PackageItem;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\ClickAndCollect\Model\PackageItem as PackageItemModel;
use Magestore\ClickAndCollect\Model\ResourceModel\PackageItem as PackageItemResourceModel;

/**
 * Class Collection
 *
 * Used for PackageItem collection
 */
class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            PackageItemModel::class,
            PackageItemResourceModel::class
        );
    }
}
