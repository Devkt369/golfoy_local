<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Model\Source\Order;

use Magento\Framework\Data\OptionSourceInterface;
use Magestore\Webpos\Api\Data\Checkout\PosOrderInterface;

/**
 * Option queueing status
 */
class QueueingStatus implements OptionSourceInterface
{
    /**
     * Get option array
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['label' => __('Processing'), 'value' => PosOrderInterface::QUEUEING_STATUS_PROCESSING],
            ['label' => __('Fail'), 'value' => PosOrderInterface::QUEUEING_STATUS_FAIL]
        ];
    }

    /**
     * Get option array
     *
     * @return array
     */
    public function getOptionArray()
    {
        return [
            PosOrderInterface::QUEUEING_STATUS_PROCESSING => __('Processing'),
            PosOrderInterface::QUEUEING_STATUS_FAIL => __('Fail')
        ];
    }

    /**
     * Get label by value
     *
     * @param string|int $value
     * @return string
     */
    public function getOptionText($value)
    {
        return isset($this->getOptionArray()[$value]) ? $this->getOptionArray()[$value] : '';
    }
}
