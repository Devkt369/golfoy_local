<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block\Adminhtml\Group;

use ArtM\ProductSlider\Api\SliderGroupRepositoryInterface;
use ArtM\ProductSlider\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use ArtM\ProductSlider\Model\Source\Status;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupCollectionFactory;

class Edit extends Template
{
    private ?array $resolvedDataValues = null;

    public function __construct(
        Context $context,
        private readonly SliderGroupRepositoryInterface $groupRepository,
        private readonly SliderCollectionFactory $sliderCollectionFactory,
        private readonly Status $status,
        private readonly FormKey $formKeyService,
        private readonly DataPersistorInterface $dataPersistor,
        private readonly StoreManagerInterface $storeManager,
        private readonly CustomerGroupCollectionFactory $customerGroupCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getGroupId(): int
    {
        return (int)$this->getRequest()->getParam('group_id');
    }

    public function isEdit(): bool
    {
        return $this->getGroupId() > 0;
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
            'group_id' => 0,
            'name' => '',
            'identifier' => '',
            'status' => 1,
            'sort_order' => 0,
            'description' => '',
            'store_view_ids' => [],
            'customer_group_ids' => [],
            'heading' => '',
            'sub_heading' => '',
            'show_desktop' => 1,
            'show_tablet' => 1,
            'show_mobile' => 1,
            'tabs' => [],
            'tabs_json' => '',
        ];

        if (!$this->isEdit()) {
            $persisted = (array)$this->dataPersistor->get('artm_productslider_group');
            if ($persisted !== []) {
                $this->dataPersistor->clear('artm_productslider_group');
                $this->resolvedDataValues = array_merge($defaults, $persisted);
                return $this->resolvedDataValues;
            }

            $this->resolvedDataValues = $defaults;
            return $this->resolvedDataValues;
        }

        try {
            $entity = $this->groupRepository->getById($this->getGroupId());
            $entityData = (array)$entity->getData();
            $values = array_merge($defaults, $entityData);
            $values = $this->hydrateFromTabsJson($values);
            $persisted = (array)$this->dataPersistor->get('artm_productslider_group');
            if ($persisted !== []) {
                $this->dataPersistor->clear('artm_productslider_group');
                $values = array_merge($values, $persisted);
                if (isset($persisted['tabs']) && is_array($persisted['tabs'])) {
                    $values['tabs'] = $persisted['tabs'];
                }
            }

            $this->resolvedDataValues = $values;
            return $this->resolvedDataValues;
        } catch (NoSuchEntityException) {
            $this->resolvedDataValues = $defaults;
            return $this->resolvedDataValues;
        }
    }

    public function getStatusOptions(): array
    {
        return $this->status->toOptionArray();
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

    public function getSliderOptions(): array
    {
        $options = [['value' => '', 'label' => (string)__('-- Please Select --')]];
        $collection = $this->sliderCollectionFactory->create();
        $collection->setOrder('title', 'ASC');

        foreach ($collection as $slider) {
            $options[] = [
                'value' => (string)$slider->getId(),
                'label' => sprintf('%s (#%d)', (string)$slider->getData('title'), (int)$slider->getId()),
            ];
        }

        return $options;
    }

    public function getSelectedValues(string $key): array
    {
        $value = $this->getDataValues()[$key] ?? [];
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value), static fn(string $v): bool => $v !== ''));
        }

        $raw = (string)$value;
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), static fn(string $v): bool => $v !== ''));
    }

    public function getBackUrl(): string
    {
        return $this->getUrl('artm_productslider/group/index');
    }

    public function getSaveUrl(): string
    {
        return $this->getUrl('artm_productslider/group/save');
    }

    public function getDeleteUrl(): string
    {
        return $this->getUrl('artm_productslider/group/delete', ['group_id' => $this->getGroupId()]);
    }

    private function hydrateFromTabsJson(array $values): array
    {
        $raw = (string)($values['tabs_json'] ?? '');
        if ($raw === '') {
            return $values;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return $values;
        }

        $settings = $decoded['settings'] ?? [];
        if (is_array($settings)) {
            if (isset($settings['store_view_ids'])) {
                $values['store_view_ids'] = is_array($settings['store_view_ids']) ? $settings['store_view_ids'] : [];
            }
            if (isset($settings['customer_group_ids'])) {
                $values['customer_group_ids'] = is_array($settings['customer_group_ids']) ? $settings['customer_group_ids'] : [];
            }
            $values['heading'] = (string)($settings['heading'] ?? $values['heading']);
            $values['sub_heading'] = (string)($settings['sub_heading'] ?? $values['sub_heading']);
            $values['show_desktop'] = (int)($settings['show_desktop'] ?? $values['show_desktop']);
            $values['show_tablet'] = (int)($settings['show_tablet'] ?? $values['show_tablet']);
            $values['show_mobile'] = (int)($settings['show_mobile'] ?? $values['show_mobile']);
        }

        if (isset($decoded['tabs']) && is_array($decoded['tabs'])) {
            $values['tabs'] = $decoded['tabs'];
        }

        return $values;
    }
}
