<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel\Schedule;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\ClickAndCollect\Model\ResourceModel\Schedule as ScheduleResourceModel;
use Magestore\ClickAndCollect\Model\Schedule as ScheduleModel;

/**
 * Resource Collection of Schedule entities
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
        $this->_init(ScheduleModel::class, ScheduleResourceModel::class);
    }
}
