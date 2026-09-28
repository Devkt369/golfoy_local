<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magestore\ClickAndCollect\Model\ResourceModel\SpecialDay as SpecialDayResourceModel;
use Magestore\ClickAndCollect\Model\SpecialDay as SpecialDayModel;

/**
 * Resource Collection of Special Day entities
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
        $this->_init(SpecialDayModel::class, SpecialDayResourceModel::class);
    }
}
