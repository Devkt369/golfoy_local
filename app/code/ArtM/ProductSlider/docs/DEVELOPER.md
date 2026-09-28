# Developer Documentation - ArtM ProductSlider

## Architecture

### Slider Rendering

- Block: `ArtM\ProductSlider\Block\Slider`
- Data provider: `ArtM\ProductSlider\Model\Frontend\SliderDataProvider`
- Card rendering template: `view/frontend/templates/slider/cards.phtml`
- Interaction JS: `view/frontend/web/js/slider.js`

### Group Rendering

- Block: `ArtM\ProductSlider\Block\Group`
- Reusable provider service: `ArtM\ProductSlider\Model\Frontend\GroupDataProvider`
- Group template: `view/frontend/templates/group/view.phtml`
- Group tab JS: `view/frontend/web/js/group-tabs.js`
- AJAX endpoint: `ArtM\ProductSlider\Controller\Ajax\GroupTab`

## Group Tabs JSON Contract

Stored in `artm_product_slider_group.tabs_json`.

```json
{
  "settings": {
    "store_view_ids": ["1"],
    "customer_group_ids": ["0", "1"],
    "heading": "Home Tabs",
    "sub_heading": "Curated collections",
    "show_desktop": 1,
    "show_tablet": 1,
    "show_mobile": 1
  },
  "tabs": [
    {
      "slider_id": 2,
      "tab_name": "Featured",
      "view_all_override": "use_slider_button",
      "manual_url": "",
      "sort_order": 1,
      "is_default": 1
    }
  ]
}
```

## AJAX Flow

1. Group page renders only default tab content.
2. Clicking another tab triggers `artm_productslider/ajax/grouptab` with:
   - `identifier` or `group_id`
   - `tab_index`
3. Endpoint returns rendered slider HTML for that tab.
4. Client caches tab HTML in memory and does not re-fetch same tab.

## CMS Shortcode Support

Plugin:
- `ArtM\ProductSlider\Plugin\Cms\TemplateFilterPlugin`

Transforms shortcodes into native block directives before CMS filter rendering.

Supported inputs:
- `{{product_slider id="15"}}`
- `{{product_slider identifier="featured-products"}}`
- `{{product_slider_group id="3"}}`
- `{{product_slider_group identifier="homepage-tabs"}}`

## Extending Group Tab Behavior

- Add new tab metadata in `tabs_json.tabs[*]`.
- Normalize and validate in:
  - `Controller/Adminhtml/Group/Save.php`
  - `Model/Frontend/GroupDataProvider.php`
- Render new metadata in:
  - `view/frontend/templates/group/view.phtml`

## Coding Guidelines

- Keep strict types in PHP files.
- Use constructor DI only.
- Avoid direct DB writes outside repositories/resource models.
- Maintain backward compatibility for existing slider URLs and block usage.
