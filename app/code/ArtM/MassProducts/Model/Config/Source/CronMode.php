<?php
namespace ArtM\MassProducts\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CronMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'run_once', 'label' => __('Run Once')],
            ['value' => 'recurring', 'label' => __('Recurring')],
        ];
    }
}
