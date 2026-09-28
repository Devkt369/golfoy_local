<?php
namespace ArtM\MassProducts\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class DraftStatusOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'incomplete', 'label' => __('Incomplete')],
            ['value' => 'ready', 'label' => __('Ready')],
        ];
    }
}
