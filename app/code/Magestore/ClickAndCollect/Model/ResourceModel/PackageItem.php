<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface;

/**
 * Class PackageItem
 *
 * Used for PackageItem resource model
 */
class PackageItem extends AbstractDb
{
    /**#@+
     * Constants related to specific db layer
     */
    const TABLE_NAME_PACKAGE = 'clickandcollect_package_item';
    /**#@-*/

    /**
     * Resource Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(self::TABLE_NAME_PACKAGE, PackageItemInterface::PACKAGE_ITEM_ID);
    }
}
