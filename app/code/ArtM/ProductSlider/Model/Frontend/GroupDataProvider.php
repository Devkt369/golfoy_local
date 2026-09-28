<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Frontend;

use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use ArtM\ProductSlider\Model\ResourceModel\SliderGroup\CollectionFactory as SliderGroupCollectionFactory;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Store\Model\StoreManagerInterface;

class GroupDataProvider
{
    public function __construct(
        private readonly SliderGroupCollectionFactory $groupCollectionFactory,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly StoreManagerInterface $storeManager,
        private readonly HttpContext $httpContext
    ) {
    }

    /**
     * Resolve a group by identifier or ID and normalize tabs/settings for frontend rendering.
     *
     * @return array<string, mixed>|null
     */
    public function getGroup(string $identifier = '', int $groupId = 0): ?array
    {
        $identifier = trim($identifier);

        $collection = $this->groupCollectionFactory->create();
        $collection->addFieldToFilter('status', 1);
        if ($groupId > 0) {
            $collection->addFieldToFilter('group_id', $groupId);
        } elseif ($identifier !== '') {
            $collection->addFieldToFilter('identifier', $identifier);
        } else {
            return null;
        }
        $collection->setPageSize(1);

        $group = $collection->getFirstItem();
        if (!$group->getId()) {
            return null;
        }

        $groupData = (array)$group->getData();
        $decoded = json_decode((string)($groupData['tabs_json'] ?? ''), true);
        $settings = is_array($decoded['settings'] ?? null) ? (array)$decoded['settings'] : [];
        $tabsRaw = is_array($decoded['tabs'] ?? null) ? (array)$decoded['tabs'] : [];

        $settings = $this->normalizeSettings($settings);
        if (!$this->isVisibleForContext($settings)) {
            return null;
        }

        $tabs = $this->normalizeTabs($tabsRaw);
        if ($tabs === []) {
            return null;
        }

        $groupData['settings'] = $settings;
        $groupData['tabs'] = $tabs;
        return $groupData;
    }

    /**
     * Build device visibility utility classes from group settings.
     */
    public function getDeviceVisibilityClass(array $settings): string
    {
        $classes = [];
        if ((int)($settings['show_desktop'] ?? 1) !== 1) {
            $classes[] = 'artm-ps-hide-desktop';
        }
        if ((int)($settings['show_tablet'] ?? 1) !== 1) {
            $classes[] = 'artm-ps-hide-tablet';
        }
        if ((int)($settings['show_mobile'] ?? 1) !== 1) {
            $classes[] = 'artm-ps-hide-mobile';
        }

        return implode(' ', $classes);
    }

    private function normalizeSettings(array $settings): array
    {
        return [
            'store_view_ids' => $this->normalizeIdStrings($settings['store_view_ids'] ?? []),
            'customer_group_ids' => $this->normalizeIdStrings($settings['customer_group_ids'] ?? []),
            'heading' => trim((string)($settings['heading'] ?? '')),
            'sub_heading' => trim((string)($settings['sub_heading'] ?? '')),
            'show_desktop' => (int)($settings['show_desktop'] ?? 1) === 1 ? 1 : 0,
            'show_tablet' => (int)($settings['show_tablet'] ?? 1) === 1 ? 1 : 0,
            'show_mobile' => (int)($settings['show_mobile'] ?? 1) === 1 ? 1 : 0,
        ];
    }

    private function normalizeTabs(array $tabsRaw): array
    {
        $tabs = [];
        foreach ($tabsRaw as $index => $tab) {
            if (!is_array($tab)) {
                continue;
            }

            $sliderId = (int)($tab['slider_id'] ?? 0);
            if ($sliderId <= 0 || (int)($tab['status'] ?? 1) !== 1) {
                continue;
            }

            $identifier = $this->getSliderIdentifierById($sliderId);
            if ($identifier === '') {
                continue;
            }

            $tabs[] = [
                'index' => (int)$index,
                'slider_id' => $sliderId,
                'slider_identifier' => $identifier,
                'tab_name' => trim((string)($tab['tab_name'] ?? '')),
                'sort_order' => (int)($tab['sort_order'] ?? 0),
                'is_default' => (int)($tab['is_default'] ?? 0) === 1 ? 1 : 0,
                'view_all_override' => (string)($tab['view_all_override'] ?? 'use_slider_button'),
                'manual_url' => trim((string)($tab['manual_url'] ?? '')),
            ];
        }

        usort($tabs, static fn(array $a, array $b): int => ($a['sort_order'] <=> $b['sort_order']));

        $defaultFound = false;
        foreach ($tabs as $i => $tab) {
            if ($tab['is_default'] === 1 && !$defaultFound) {
                $defaultFound = true;
            } else {
                $tabs[$i]['is_default'] = 0;
            }
        }
        if (!$defaultFound && $tabs !== []) {
            $tabs[0]['is_default'] = 1;
        }

        return $tabs;
    }

    private function normalizeIdStrings(mixed $value): array
    {
        if (is_array($value)) {
            $parts = $value;
        } else {
            $parts = explode(',', (string)$value);
        }

        $ids = [];
        foreach ($parts as $part) {
            $id = (int)trim((string)$part);
            if ($id >= 0) {
                $ids[] = (string)$id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function isVisibleForContext(array $settings): bool
    {
        $storeIds = $settings['store_view_ids'];
        if ($storeIds !== []) {
            $storeId = (string)((int)$this->storeManager->getStore()->getId());
            if (!in_array($storeId, $storeIds, true)) {
                return false;
            }
        }

        $customerGroupIds = $settings['customer_group_ids'];
        if ($customerGroupIds !== []) {
            $groupId = (string)((int)$this->httpContext->getValue(CustomerContext::CONTEXT_GROUP));
            if (!in_array($groupId, $customerGroupIds, true)) {
                return false;
            }
        }

        return true;
    }

    private function getSliderIdentifierById(int $sliderId): string
    {
        try {
            $slider = $this->sliderRepository->getById($sliderId);
            if ((int)$slider->getData('status') !== 1) {
                return '';
            }
            return trim((string)$slider->getData('identifier'));
        } catch (\Throwable) {
            return '';
        }
    }
}
