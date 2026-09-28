<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Model\Source\SpecialDay;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;

/**
 * Source model
 */
class Repeat extends AbstractSource
{
    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        return [
            ['value' => SpecialDayInterface::REPEAT_NONE, 'label' => __('None')],
            ['value' => SpecialDayInterface::REPEAT_YEARLY, 'label' => __('Yearly')]
        ];
    }
}
