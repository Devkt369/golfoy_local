# ArtM ProductSlider

Magento 2 module for rendering product sliders and slider groups (tabbed sliders) on storefront and CMS content.

## Features

- Slider rendering by identifier or ID.
- Slider Group rendering with tabbed sliders.
- Drag-drop tab ordering (admin).
- Custom tab title.
- Default active tab.
- View All override per tab (`use_slider_button`, `manual_url`, `hide`).
- Group heading and sub heading.
- Device visibility controls (`desktop`, `tablet`, `mobile`) for groups.
- Customer Group visibility for groups.
- AJAX tab switching for groups.
- Load only active tab initially; lazy-load remaining tabs on demand.
- Client-side tab cache for loaded tab HTML.

## Frontend Integration

### 1) Magento Widget

- `ArtM Product Slider`
  - Params: `slider_id` or `identifier`
- `ArtM Product Slider Group`
  - Params: `group_id` or `identifier`

### 2) CMS Static Block Shortcodes

Supported syntax:

- `{{product_slider id="15"}}`
- `{{product_slider identifier="featured-products"}}`
- `{{product_slider_group id="3"}}`
- `{{product_slider_group identifier="homepage-tabs"}}`

### 3) CMS Block Directive (native)

- Slider:
  - `{{block class="ArtM\\ProductSlider\\Block\\Slider" identifier="featured-products" template="ArtM_ProductSlider::slider/view.phtml"}}`
- Group:
  - `{{block class="ArtM\\ProductSlider\\Block\\Group" identifier="homepage-tabs" template="ArtM_ProductSlider::group/view.phtml"}}`

### 4) PHTML Integration

- Slider:
```php
<?php echo $block->getLayout()
    ->createBlock(\ArtM\ProductSlider\Block\Slider::class)
    ->setData('identifier', 'featured-products')
    ->setTemplate('ArtM_ProductSlider::slider/view.phtml')
    ->toHtml(); ?>
```

- Group:
```php
<?php echo $block->getLayout()
    ->createBlock(\ArtM\ProductSlider\Block\Group::class)
    ->setData('identifier', 'homepage-tabs')
    ->setTemplate('ArtM_ProductSlider::group/view.phtml')
    ->toHtml(); ?>
```

### 5) Layout XML Integration

```xml
<referenceContainer name="content">
    <block class="ArtM\ProductSlider\Block\Slider"
           name="artm.slider.featured"
           template="ArtM_ProductSlider::slider/view.phtml">
        <arguments>
            <argument name="identifier" xsi:type="string">featured-products</argument>
        </arguments>
    </block>

    <block class="ArtM\ProductSlider\Block\Group"
           name="artm.slider.group.homepage"
           template="ArtM_ProductSlider::group/view.phtml">
        <arguments>
            <argument name="identifier" xsi:type="string">homepage-tabs</argument>
        </arguments>
    </block>
</referenceContainer>
```

## Performance Notes

- Slider group renders only the active tab at first load.
- Non-active tabs are loaded via AJAX endpoint `artm_productslider/ajax/grouptab`.
- Loaded tabs are cached client-side to avoid repeat network calls.
- AJAX responses include cache headers for edge cache friendliness.

## Admin Notes

- Group tab preview in admin mirrors tab ordering/title/default and override behavior.
- Group visibility settings are stored in `tabs_json.settings`:
  - `store_view_ids`
  - `customer_group_ids`
  - `heading`
  - `sub_heading`
  - `show_desktop`
  - `show_tablet`
  - `show_mobile`

## Standards

- No core overrides.
- No `ObjectManager` usage.
- Strict types enabled in PHP classes.
- Marketplace-friendly structure and DI usage.
