<?php

namespace Meetanshi\Sms\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Apiprovider
 * @package Meetanshi\Sms\Model\Config\Source
 */
class Apiprovider implements ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'msg', 'label' => __('TextLocal')],
            ['value' => 'msg91', 'label' => __('MSG91')]
            // ['value' => 'bulksms', 'label' => __('BulkSMS')],
            // ['value' => 'textlocal', 'label' => __('TextLocal')],
            // ['value' => 'other', 'label' => __('Other')]
        ];
    }
}
