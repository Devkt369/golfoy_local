<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Model\Source\Schedule;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Source model
 */
class Minutes extends AbstractSource
{
    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        $hoursArray = [];
        for ($index = 0; $index < 60; $index++) {
            $hoursArray[] = [
                'value' => str_pad((string) $index, 2, "0", STR_PAD_LEFT),
                'label' => str_pad((string) $index, 2, "0", STR_PAD_LEFT)
            ];
        }
        return $hoursArray;
    }
}
