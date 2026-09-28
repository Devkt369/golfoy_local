<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\ViewModel;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Block\ArgumentInterface;

class SliderRenderer implements ArgumentInterface
{
    /** @var array<int, string> */
    private array $categoryUrlCache = [];

    public function __construct(
        private readonly Json $json,
        private readonly CategoryRepositoryInterface $categoryRepository
    ) {
    }

    /**
     * Resolves the slider/tab's "View All" button URL.
     *
     * `button_link` is stored differently depending on `button_link_type`:
     * for `manual`, it's already a real URL; for `category`, it's just the
     * raw category id (the admin form only collects an id, see
     * button_link_category in slider/edit.phtml) — that id must be
     * resolved to the category's actual frontend URL here, otherwise it
     * renders as a bare relative path (e.g. href="721" resolving to
     * "example.com/721") instead of the category page.
     */
    public function getButtonUrl(array $sliderData): string
    {
        $linkType = (string)($sliderData['button_link_type'] ?? 'manual');
        $rawLink = trim((string)($sliderData['button_link'] ?? ''));

        if ($rawLink === '') {
            return '';
        }

        if ($linkType !== 'category') {
            return $rawLink;
        }

        $categoryId = (int)$rawLink;
        if ($categoryId <= 0) {
            return '';
        }

        if (array_key_exists($categoryId, $this->categoryUrlCache)) {
            return $this->categoryUrlCache[$categoryId];
        }

        $url = '';
        try {
            $url = (string)$this->categoryRepository->get($categoryId)->getUrl();
        } catch (\Throwable) {
            $url = '';
        }

        $this->categoryUrlCache[$categoryId] = $url;

        return $url;
    }

    public function getOrderedComponents(array $sliderData): array
    {
        $components = array_values(array_filter(array_map('trim', explode(',', (string)($sliderData['card_components'] ?? '')))));
        if ($components === []) {
            $components = ['image', 'product_name', 'price', 'add_to_cart'];
        }

        return $components;
    }

    public function getCustomAttributeCodes(array $sliderData): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string)($sliderData['custom_attributes'] ?? '')))));
    }

    public function getDeviceVisibilityClass(array $sliderData): string
    {
        $classes = [];
        if ((int)($sliderData['show_desktop'] ?? 1) !== 1) {
            $classes[] = 'artm-ps-hide-desktop';
        }
        if ((int)($sliderData['show_tablet'] ?? 1) !== 1) {
            $classes[] = 'artm-ps-hide-tablet';
        }
        if ((int)($sliderData['show_mobile'] ?? 1) !== 1) {
            $classes[] = 'artm-ps-hide-mobile';
        }

        return implode(' ', $classes);
    }

    public function getInitialPageSize(array $sliderData): int
    {
        $displayType = (string)($sliderData['display_type'] ?? 'slider');
        if ($displayType === 'grid') {
            $explicitLimit = (int)($sliderData['product_limit'] ?? 0);
            if ($explicitLimit > 0) {
                return max(1, $explicitLimit);
            }

            return max(1, (int)($sliderData['max_products_desktop'] ?? 12));
        }

        return max(1, (int)($sliderData['desktop_items'] ?? 4));
    }

    public function getJsConfig(array $sliderData, string $ajaxUrl): string
    {
        $config = [
            'ajaxUrl' => $ajaxUrl,
            'identifier' => (string)($sliderData['identifier'] ?? ''),
            'displayType' => (string)($sliderData['display_type'] ?? 'slider'),
            'itemsDesktop' => max(1, (int)($sliderData['desktop_items'] ?? 4)),
            'itemsTablet' => max(1, (int)($sliderData['tablet_items'] ?? 2)),
            'itemsMobile' => max(1, (int)($sliderData['mobile_items'] ?? 1)),
            'gridDesktop' => max(1, (int)($sliderData['grid_desktop_columns'] ?? 4)),
            'gridTablet' => max(1, (int)($sliderData['grid_tablet_columns'] ?? 2)),
            'gridMobile' => max(1, (int)($sliderData['grid_mobile_columns'] ?? 1)),
            'arrows' => (int)($sliderData['show_arrows'] ?? 1) === 1,
            'dots' => (int)($sliderData['show_dots'] ?? 1) === 1,
            'infinite' => (int)($sliderData['infinite_loop'] ?? 1) === 1,
            'autoplay' => (int)($sliderData['autoplay'] ?? 0) === 1,
            'autoplayDelay' => max(500, (int)($sliderData['autoplay_delay'] ?? 3000)),
            'lazyLoad' => (int)($sliderData['lazy_load'] ?? 1) === 1,
            'swipe' => (int)($sliderData['swipe'] ?? 1) === 1,
            'mouseDrag' => (int)($sliderData['mouse_drag'] ?? 1) === 1,
            'touchSupport' => (int)($sliderData['touch_support'] ?? 1) === 1,
            'animationSpeed' => max(100, (int)($sliderData['animation_speed'] ?? 300)),
            'spaceBetween' => max(0, (int)($sliderData['space_between'] ?? 16)),
            'initialPageSize' => $this->getInitialPageSize($sliderData),
        ];

        return $this->json->serialize($config);
    }
}
