<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

class CardComponents implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        return [
            ['value' => 'image', 'label' => __('Image')],
            ['value' => 'hover_image', 'label' => __('Hover Image')],
            ['value' => 'product_name', 'label' => __('Name')],
            ['value' => 'short_description', 'label' => __('Description')],
            ['value' => 'sku', 'label' => __('SKU')],
            ['value' => 'price', 'label' => __('Price')],
            ['value' => 'special_price', 'label' => __('Special Price')],
            ['value' => 'discount_percent', 'label' => __('Discount')],
            ['value' => 'rating', 'label' => __('Rating')],
            ['value' => 'review_count', 'label' => __('Reviews')],
            ['value' => 'stock_status', 'label' => __('Stock')],
            ['value' => 'add_to_cart', 'label' => __('Add to Cart')],
            ['value' => 'wishlist', 'label' => __('Wishlist')],
            ['value' => 'compare', 'label' => __('Compare')],
            ['value' => 'custom_attributes', 'label' => __('Custom Attributes')],
        ];
    }
}
