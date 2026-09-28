<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel\Package;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\ClickAndCollect\Model\Package as PackageModel;
use Magestore\ClickAndCollect\Model\ResourceModel\Package as PackageResourceModel;

/**
 * Class Collection
 *
 * Used for Package collection
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
            PackageModel::class,
            PackageResourceModel::class
        );
    }
}
