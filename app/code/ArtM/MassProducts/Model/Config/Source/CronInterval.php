<?php
namespace ArtM\MassProducts\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CronInterval implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'minutes', 'label' => __('Every X Minutes')],
            ['value' => 'hourly', 'label' => __('Hourly')],
            ['value' => 'daily', 'label' => __('Daily')],
        ];
    }
}
