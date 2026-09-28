<?php
namespace ArtM\MassProducts\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class OperationTypeOptions implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'create', 'label' => __('Create')],
            ['value' => 'update', 'label' => __('Update')],
        ];
    }
}
