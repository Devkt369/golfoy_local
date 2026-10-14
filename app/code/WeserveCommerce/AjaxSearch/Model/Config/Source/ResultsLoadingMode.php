<?php
namespace WeserveCommerce\AjaxSearch\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ResultsLoadingMode implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'pagination', 'label' => __('Numbered Pagination')],
            ['value' => 'infinite_scroll', 'label' => __('Infinite Scroll')]
        ];
    }
}
