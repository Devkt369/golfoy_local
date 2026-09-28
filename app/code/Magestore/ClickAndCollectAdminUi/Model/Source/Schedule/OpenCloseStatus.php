<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Model\Source\Schedule;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleInterface;

/**
 * Source model
 */
class OpenCloseStatus extends AbstractSource
{
    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        return [
            ['value' => ScheduleInterface::OPEN_STATUS, 'label' => __('Open')],
            ['value' => ScheduleInterface::CLOSE_STATUS, 'label' => __('Close')]
        ];
    }
}
