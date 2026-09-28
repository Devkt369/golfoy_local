<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block\Adminhtml\Slider;

use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use ArtM\ProductSlider\Model\Source\ButtonLinkType;
use ArtM\ProductSlider\Model\Source\CardComponents;
use ArtM\ProductSlider\Model\Source\DisplayType;
use ArtM\ProductSlider\Model\Source\ImageRole;
use ArtM\ProductSlider\Model\Source\ProductSourceType;
use ArtM\ProductSlider\Model\Source\SlidesToShow;
use ArtM\ProductSlider\Model\Source\SliderGroups;
use ArtM\ProductSlider\Model\Source\SortingType;
use ArtM\ProductSlider\Model\Source\Status;
use ArtM\ProductSlider\Model\Source\YesNo;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as ProductAttributeCollectionFactory;

class Edit extends Template
{
    private const FALLBACK_PAYLOAD_KEY = '__artm_slider_fallback';
    private ?array $resolvedDataValues = null;

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly SliderGroups $sliderGroups,
        private readonly Status $status,
        private readonly YesNo $yesNo,
        private readonly ProductSourceType $productSourceType,
        private readonly SortingType $sortingType,
        private readonly DisplayType $displayType,
        private readonly ButtonLinkType $buttonLinkType,
        private readonly CardComponents $cardComponents,
        private readonly ImageRole $imageRole,
        private readonly SlidesToShow $slidesToShow,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        private readonly FormKey $formKeyService,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly ProductAttributeCollectionFactory $productAttributeCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getSliderId(): int
    {
        return (int)$this->getRequest()->getParam('slider_id');
    }

    public function isEdit(): bool
    {
        return $this->getSliderId() > 0;
    }

    public function getFormKey(): string
    {
        return $this->formKeyService->getFormKey();
    }

    public function getDataValues(): array
    {
        if ($this->resolvedDataValues !== null) {
            return $this->resolvedDataValues;
        }

        $defaults = [
            'slider_id' => 0,
            'group_id' => '',
            'group_ids' => [],
            'title' => '',
            'identifier' => '',
            'store_view_ids' => '',
            'customer_group_ids' => '',
            'from_date' => '',
            'to_date' => '',
            'status' => 1,
            'heading' => '',
            'sub_heading' => '',
            'content_description' => '',
            'button_text' => '',
            'button_link' => '',
            'button_link_type' => 'manual',
            'show_desktop' => 1,
            'show_tablet' => 1,
            'show_mobile' => 1,
            'source_type' => 'category',
            'source_value' => '',
            'category_ids' => '',
            'allow_multiple_categories' => 1,
            'include_child_categories' => 1,
            'exclude_product_ids' => '',
            'source_product_ids' => '',
            'mixed_category_ids' => '',
            'mixed_product_ids' => '',
            'mixed_excluded_product_ids' => '',
            'mixed_priority' => 'category_then_manual',
            'max_products_desktop' => 12,
            'max_products_tablet' => 8,
            'max_products_mobile' => 4,
            'sorting_type' => 'newest',
            'manual_sort_product_ids' => '',
            'custom_sort_attribute' => '',
            'product_limit' => 12,
            'slides_to_show' => '4',
            'display_type' => 'slider',
            'desktop_items' => 4,
            'tablet_items' => 2,
            'mobile_items' => 1,
            'space_between' => 16,
            'show_arrows' => 1,
            'show_dots' => 1,
            'infinite_loop' => 1,
            'autoplay' => 0,
            'autoplay_delay' => 3000,
            'swipe' => 1,
            'mouse_drag' => 1,
            'touch_support' => 1,
            'lazy_load' => 1,
            'animation_speed' => 300,
            'grid_desktop_columns' => 4,
            'grid_tablet_columns' => 2,
            'grid_mobile_columns' => 1,
            'card_components' => 'image,product_name,price,add_to_cart',
            'custom_attributes' => '',
            'hover_image_enabled' => 0,
            'image_role' => 'small_image',
            'autoplay_speed' => 3000,
            'show_name' => 1,
            'show_price' => 1,
            'show_add_to_cart' => 1,
            'remove_brand_from_name' => 0,
            'preview_notes' => '',
            'sort_order' => 0,
        ];

        if (!$this->isEdit()) {
            $persisted = (array)$this->dataPersistor->get('artm_productslider_slider');
            if ($persisted !== []) {
                $this->dataPersistor->clear('artm_productslider_slider');
                $values = array_merge($defaults, $persisted);
                $values['group_ids'] = $this->normalizeGroupIds($values['group_ids'] ?? [], $values['group_id'] ?? '');
                $this->resolvedDataValues = $values;
                return $this->resolvedDataValues;
            }

            $defaults['group_ids'] = $this->normalizeGroupIds($defaults['group_ids'], $defaults['group_id']);
            $this->resolvedDataValues = $defaults;
            return $this->resolvedDataValues;
        }

        try {
            $entity = $this->sliderRepository->getById($this->getSliderId());
            $entityData = (array)$entity->getData();
            $values = array_merge($defaults, $entityData);
            $values = $this->mergeFallbackValues($values, $entityData);
            $persisted = (array)$this->dataPersistor->get('artm_productslider_slider');
            if ($persisted !== []) {
                $this->dataPersistor->clear('artm_productslider_slider');
                $values = array_merge($values, $persisted);
            }

            $values['group_ids'] = $this->normalizeGroupIds($values['group_ids'] ?? [], $values['group_id'] ?? '');

            $this->resolvedDataValues = $values;
            return $this->resolvedDataValues;
        } catch (NoSuchEntityException) {
            $defaults['group_ids'] = $this->normalizeGroupIds($defaults['group_ids'], $defaults['group_id']);
            $this->resolvedDataValues = $defaults;
            return $this->resolvedDataValues;
        }
    }

    public function getSelectedGroupIds(): array
    {
        return $this->normalizeGroupIds(
            $this->getDataValues()['group_ids'] ?? [],
            $this->getDataValues()['group_id'] ?? ''
        );
    }

    public function getSlidesToShowSelected(): array
    {
        $raw = (string)($this->getDataValues()['slides_to_show'] ?? '');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn(string $v): bool => $v !== ''));
    }

    public function getSelectedValues(string $key): array
    {
        $raw = (string)($this->getDataValues()[$key] ?? '');
        if ($raw === '') {
            return [];
        }
        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn(string $v): bool => $v !== ''));
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/index');
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/save');
    }

    public function getDeleteUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/delete', ['slider_id' => $this->getSliderId()]);
    }

    public function getGroupOptions(): array
    {
        return $this->sliderGroups->toOptionArray();
    }

    public function getStatusOptions(): array
    {
        return $this->status->toOptionArray();
    }

    public function getYesNoOptions(): array
    {
        return $this->yesNo->toOptionArray();
    }

    public function getSourceTypeOptions(): array
    {
        return $this->productSourceType->toOptionArray();
    }

    public function getSortingTypeOptions(): array
    {
        return $this->sortingType->toOptionArray();
    }

    public function getDisplayTypeOptions(): array
    {
        return $this->displayType->toOptionArray();
    }

    public function getButtonLinkTypeOptions(): array
    {
        return $this->buttonLinkType->toOptionArray();
    }

    public function getSlidesToShowOptions(): array
    {
        return $this->slidesToShow->toOptionArray();
    }

    public function getCardComponentOptions(): array
    {
        return $this->cardComponents->toOptionArray();
    }

    public function getProductAttributeOptions(): array
    {
        $collection = $this->productAttributeCollectionFactory->create();
        $collection->addFieldToFilter('frontend_label', ['neq' => '']);

        $options = [];
        foreach ($collection as $attribute) {
            $code = (string)$attribute->getAttributeCode();
            $label = trim((string)$attribute->getFrontendLabel());
            if ($code === '' || $label === '') {
                continue;
            }

            $options[] = ['value' => 'attr:' . $code, 'label' => $label];
        }

        usort($options, static fn(array $a, array $b): int => strcasecmp((string)$a['label'], (string)$b['label']));

        return $options;
    }

    public function getImageRoleOptions(): array
    {
        return $this->imageRole->toOptionArray();
    }

    public function getStoreViewOptions(): array
    {
        $options = [];
        foreach ($this->storeManager->getStores() as $store) {
            $options[] = ['value' => (string)$store->getId(), 'label' => (string)$store->getName()];
        }
        return $options;
    }

    public function getCustomerGroupOptions(): array
    {
        $options = [];
        $collection = $this->customerGroupCollectionFactory->create();
        foreach ($collection as $group) {
            $options[] = ['value' => (string)$group->getId(), 'label' => (string)$group->getData('customer_group_code')];
        }
        return $options;
    }

    private function mergeFallbackValues(array $values, array $entityData): array
    {
        $sourceValue = (string)($entityData['source_value'] ?? '');
        if ($sourceValue === '') {
            return $values;
        }

        $decoded = json_decode($sourceValue, true);
        if (!is_array($decoded)) {
            return $values;
        }

        $payload = $decoded[self::FALLBACK_PAYLOAD_KEY] ?? null;
        if (!is_array($payload)) {
            return $values;
        }

        foreach ($payload as $key => $val) {
            if (array_key_exists((string)$key, $entityData) && $entityData[(string)$key] !== null && $entityData[(string)$key] !== '') {
                continue;
            }
            $values[(string)$key] = $val;
        }

        return $values;
    }

    private function normalizeGroupIds(mixed $groupIds, mixed $legacyGroupId): array
    {
        $normalized = [];

        if (is_array($groupIds)) {
            foreach ($groupIds as $groupId) {
                $id = (int)$groupId;
                if ($id > 0) {
                    $normalized[] = (string)$id;
                }
            }
        } elseif (is_string($groupIds) && trim($groupIds) !== '') {
            foreach (explode(',', $groupIds) as $groupId) {
                $id = (int)trim($groupId);
                if ($id > 0) {
                    $normalized[] = (string)$id;
                }
            }
        }

        $legacy = (int)$legacyGroupId;
        if ($legacy > 0 && !in_array((string)$legacy, $normalized, true)) {
            array_unshift($normalized, (string)$legacy);
        }

        return array_values(array_unique($normalized));
    }
}
