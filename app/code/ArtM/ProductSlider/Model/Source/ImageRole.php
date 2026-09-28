<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class ImageRole implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'image', 'label' => __('Base Image')],
            ['value' => 'small_image', 'label' => __('Small Image')],
            ['value' => 'thumbnail', 'label' => __('Thumbnail')],
            ['value' => 'category_image', 'label' => __('Category Image')],
        ];
    }
}
