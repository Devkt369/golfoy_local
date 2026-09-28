<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformance\Model\Source\Adminhtml;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Mode
 *
 * Source model for mode
 */
class Mode implements OptionSourceInterface
{

    const ONLINE = '1';
    const OFFLINE = '0';

    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Online'),
                'value' =>  self::ONLINE,
            ],
            [
                'label' => __('Offline'),
                'value' =>  self::OFFLINE,
            ],
        ];
    }
}
