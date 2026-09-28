<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\Source\Adminhtml;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class PerformanceRate
 *
 * Performance Rate Source
 */
class PerformanceRate implements OptionSourceInterface
{

    const SLOW = 0;
    const NORMAL = 1;
    const GOOD = 2;

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Slow'),
                'value' =>  self::SLOW,
            ],
            [
                'label' => __('Normal'),
                'value' =>  self::NORMAL,
            ],
            [
                'label' => __('Good'),
                'value' =>  self::GOOD,
            ]
        ];
    }
}
