<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block\Adminhtml\Slider;

use ArtM\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\StoreManagerInterface;

class Index extends Template
{
    private ?array $items = null;

    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly FormKey $formKeyService,
        private readonly ResourceConnection $resourceConnection,
        private readonly StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getItems(): array
    {
        if ($this->items !== null) {
            return $this->items;
        }

        $collection = $this->collectionFactory->create();
        $request = $this->getRequest();

        $search = trim((string)$request->getParam('q', ''));
        if ($search !== '') {
            $collection->addFieldToFilter(
                ['title', 'identifier'],
                [
                    ['like' => '%' . $search . '%'],
                    ['like' => '%' . $search . '%'],
                ]
            );
        }

        foreach (['status', 'source_type', 'display_type'] as $filterField) {
            $value = (string)$request->getParam($filterField, '');
            if ($value !== '') {
                $collection->addFieldToFilter($filterField, $value);
            }
        }

        $storeView = (string)$request->getParam('store_view_ids', '');
        if ($storeView !== '') {
            $collection->addFieldToFilter('store_view_ids', ['like' => '%'.$storeView.'%']);
        }

        $collection->setOrder('slider_id', 'DESC');
        $items = $collection->getItems();

        foreach ($items as $item) {
            $item->setData('total_products', $this->getTotalProducts((int)$item->getId()));
        }

        $this->items = $items;
        return $items;
    }

    public function getFilters(): array
    {
        $request = $this->getRequest();
        return [
            'q' => (string)$request->getParam('q', ''),
            'status' => (string)$request->getParam('status', ''),
            'source_type' => (string)$request->getParam('source_type', ''),
            'display_type' => (string)$request->getParam('display_type', ''),
            'store_view_ids' => (string)$request->getParam('store_view_ids', ''),
        ];
    }

    public function getFilterUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/index');
    }

    public function getStoreViewOptions(): array
    {
        $options = [['value' => '', 'label' => (string)__('All Store Views')]];
        foreach ($this->storeManager->getStores() as $store) {
            $options[] = ['value' => (string)$store->getId(), 'label' => (string)$store->getName()];
        }
        return $options;
    }

    public function getSourceTypeOptions(): array
    {
        return [
            ['value' => '', 'label' => __('All Sources')],
            ['value' => 'category', 'label' => __('Category')],
            ['value' => 'products', 'label' => __('Products')],
            ['value' => 'mixed', 'label' => __('Mixed')],
        ];
    }

    public function getDisplayTypeOptions(): array
    {
        return [
            ['value' => '', 'label' => __('All Display Types')],
            ['value' => 'slider', 'label' => __('Slider')],
            ['value' => 'grid', 'label' => __('Grid')],
        ];
    }

    private function getTotalProducts(int $sliderId): int
    {
        $connection = $this->resourceConnection->getConnection();
        $table = $this->resourceConnection->getTableName('artm_product_slider_products');
        $select = $connection->select()
            ->from($table, ['cnt' => 'COUNT(*)'])
            ->where('slider_id = ?', $sliderId);
        return (int)$connection->fetchOne($select);
    }

    public function getFormKey(): string
    {
        return $this->formKeyService->getFormKey();
    }

    public function getAddUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/new');
    }

    public function getEditUrl(int $sliderId): string
    {
        return $this->getUrl('artm_productslider/slider/edit', ['slider_id' => $sliderId]);
    }

    public function getDeleteUrl(int $sliderId): string
    {
        return $this->getUrl('artm_productslider/slider/delete', ['slider_id' => $sliderId]);
    }

    public function getDuplicateUrl(int $sliderId): string
    {
        return $this->getUrl('artm_productslider/slider/duplicate', ['slider_id' => $sliderId]);
    }

    public function getMassEnableUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/massenable');
    }

    public function getMassDisableUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/massdisable');
    }

    public function getMassDeleteUrl(): string
    {
        return $this->getUrl('artm_productslider/slider/massdelete');
    }

    public function getStatusLabel(int $status): string
    {
        return $status === 1 ? (string)__('Enabled') : (string)__('Disabled');
    }

    public function getStoreViewLabel(string $storeViewIds): string
    {
        $ids = array_values(array_filter(array_map('trim', explode(',', $storeViewIds)), static fn(string $v): bool => $v !== ''));
        if ($ids === []) {
            return (string)__('All Store Views');
        }
        $labels = [];
        foreach ($this->storeManager->getStores() as $store) {
            if (in_array((string)$store->getId(), $ids, true)) {
                $labels[] = $store->getName();
            }
        }
        return $labels !== [] ? implode(', ', $labels) : (string)__('Custom');
    }
}
