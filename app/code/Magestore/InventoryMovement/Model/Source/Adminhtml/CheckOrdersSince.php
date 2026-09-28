<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovement\Model\Source\Adminhtml;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

/**
 * Check Orders Since model
 */
class CheckOrdersSince extends AbstractSource
{
    const SINCE_24H = '24h';
    const SINCE_7DAYS = '7days';
    const SINCE_30DAYS = '30days';
    const SINCE_2MTD = '2MTD';
    const SINCE_6MTD = '6MTD';

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        return [
            ['value' => self::SINCE_24H, 'label' => __('Last 24 hours')],
            ['value' => self::SINCE_7DAYS, 'label' => __('Last 7 days')],
            ['value' => self::SINCE_30DAYS, 'label' => __('Last 30 days')],
            ['value' => self::SINCE_2MTD, 'label' => __('2MTD')],
            ['value' => self::SINCE_6MTD, 'label' => __('6MTD')]
        ];
    }
}
