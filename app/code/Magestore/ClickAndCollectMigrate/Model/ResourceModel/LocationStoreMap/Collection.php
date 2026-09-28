<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\ClickAndCollectMigrate\Model\LocationStoreMap as Model;
use Magestore\ClickAndCollectMigrate\Model\ResourceModel\LocationStoreMap as ResourceModel;

/**
 * Pos Location And Store Pickup Map Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'webpos_location_storepickup_store_map_collection';

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
