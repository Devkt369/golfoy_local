<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Model\Frontend;

use ArtM\ProductSlider\Model\ProductEngine;
use ArtM\ProductSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\StoreManagerInterface;

class SliderDataProvider
{
    public function __construct(
        private readonly SliderCollectionFactory $sliderCollectionFactory,
        private readonly ProductEngine $productEngine,
        private readonly ProductCollectionFactory $productCollectionFactory,
        private readonly StoreManagerInterface $storeManager,
        private readonly HttpContext $httpContext,
        private readonly TimezoneInterface $timezone,
        private readonly Visibility $productVisibility
    ) {
    }

    public function getSliderByIdentifier(string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') {
            return null;
        }

        $collection = $this->sliderCollectionFactory->create();
        $collection->addFieldToFilter('identifier', $identifier);
        $collection->addFieldToFilter('status', 1);
        $collection->setPageSize(1);

        $slider = $collection->getFirstItem();
        if (!$slider->getId()) {
            return null;
        }

        $sliderData = (array)$slider->getData();
        if (!$this->isVisibleForCurrentContext($sliderData)) {
            return null;
        }

        return $sliderData;
    }

    public function getProductPage(array $sliderData, int $page, int $pageSize): array
    {
        $resolved = $this->productEngine->resolvePageIds($sliderData, $page, $pageSize);
        $ids = $resolved['ids'];

        if ($ids === []) {
            return [
                'items' => [],
                'total' => (int)$resolved['total'],
                'page' => (int)$resolved['page'],
                'page_size' => (int)$resolved['page_size'],
                'has_more' => (bool)$resolved['has_more'],
            ];
        }

        $collection = $this->productCollectionFactory->create();
        $collection->setStoreId((int)$this->storeManager->getStore()->getId());
        $collection->addStoreFilter((int)$this->storeManager->getStore()->getId());
        $collection->addIdFilter($ids);
        $collection->addAttributeToSelect(array_values(array_unique(array_merge([
            'name',
            'small_image',
            'thumbnail',
            'short_description',
            'sku',
            'price',
            'special_price',
            'special_from_date',
            'special_to_date',
            'description',
        ], $this->getExtraAttributeCodes($sliderData)))));
        $collection->addAttributeToFilter('status', Status::STATUS_ENABLED);
        $collection->setVisibility($this->productVisibility->getVisibleInCatalogIds());
        // Salability (MSI-aware, correctly aggregating configurable/bundle
        // children) is already authoritatively resolved upstream in
        // ProductEngine::filterDisplayableIds() before $ids ever reaches
        // here — not re-checked via the legacy per-row stock flag, which
        // can disagree with MSI's real computation.
        $collection->getSelect()->order(new \Zend_Db_Expr('FIELD(e.entity_id, ' . implode(',', array_map('intval', $ids)) . ')'));

        $items = [];
        foreach ($collection as $product) {
            $items[] = $product;
        }

        return [
            'items' => $items,
            'total' => (int)$resolved['total'],
            'page' => (int)$resolved['page'],
            'page_size' => (int)$resolved['page_size'],
            'has_more' => (bool)$resolved['has_more'],
        ];
    }

    private function getExtraAttributeCodes(array $sliderData): array
    {
        $codes = [];

        foreach (explode(',', (string)($sliderData['custom_attributes'] ?? '')) as $code) {
            $code = trim($code);
            if ($code !== '') {
                $codes[] = $code;
            }
        }

        foreach (explode(',', (string)($sliderData['card_components'] ?? '')) as $component) {
            $component = trim($component);
            if (str_starts_with($component, 'attr:')) {
                $code = trim(substr($component, 5));
                if ($code !== '') {
                    $codes[] = $code;
                }
            }
        }

        if ((int)($sliderData['remove_brand_from_name'] ?? 0) === 1) {
            $codes[] = 'brand';
        }

        return array_values(array_unique($codes));
    }

    private function isVisibleForCurrentContext(array $sliderData): bool
    {
        $storeId = (int)$this->storeManager->getStore()->getId();
        $storeIds = $this->parseCsvIds((string)($sliderData['store_view_ids'] ?? ''));
        if ($storeIds !== [] && !in_array($storeId, $storeIds, true)) {
            return false;
        }

        $customerGroupIds = $this->parseCsvIds((string)($sliderData['customer_group_ids'] ?? ''));
        $currentGroupId = (int)$this->httpContext->getValue(CustomerContext::CONTEXT_GROUP);
        if ($customerGroupIds !== [] && !in_array($currentGroupId, $customerGroupIds, true)) {
            return false;
        }

        $now = $this->timezone->date();
        $configTimezone = new \DateTimeZone($this->timezone->getConfigTimezone());

        $fromRaw = trim((string)($sliderData['from_date'] ?? ''));
        if ($fromRaw !== '') {
            try {
                if ($now < new \DateTime($fromRaw, $configTimezone)) {
                    return false;
                }
            } catch (\Throwable) {
            }
        }

        $toRaw = trim((string)($sliderData['to_date'] ?? ''));
        if ($toRaw !== '') {
            try {
                if ($now > new \DateTime($toRaw, $configTimezone)) {
                    return false;
                }
            } catch (\Throwable) {
            }
        }

        return (int)($sliderData['status'] ?? 0) === 1;
    }

    private function parseCsvIds(string $csv): array
    {
        if ($csv === '') {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', explode(',', $csv)), static fn(int $v): bool => $v >= 0)));
    }
}
