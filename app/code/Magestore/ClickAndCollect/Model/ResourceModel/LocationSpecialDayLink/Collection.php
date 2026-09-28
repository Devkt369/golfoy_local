<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\ClickAndCollect\Model\ResourceModel\LocationSpecialDayLink as LocationSpecialDayLinkResourceModel;
use Magestore\ClickAndCollect\Model\LocationSpecialDayLink as LocationSpecialDayLinkModel;

/**
 * Resource Collection of Location Special Day Link entities
 *
 * @api
 */
class Collection extends AbstractCollection
{
    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        $this->_init(LocationSpecialDayLinkModel::class, LocationSpecialDayLinkResourceModel::class);
    }
}
