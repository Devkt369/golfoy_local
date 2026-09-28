<?php
namespace ArtM\CategoryPage\Model;

use ArtM\CategoryPage\Api\CategoryProviderInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Store\Model\StoreManagerInterface;

class CategoryProvider implements CategoryProviderInterface
{
    private const CACHE_PREFIX = 'artm_categorypage_';
    private const CACHE_TAG = 'ARTM_CATEGORYPAGE';

    private $categoryCollectionFactory;
    private $productCollectionFactory;
    private $categoryRepository;
    private $storeManager;
    private $configRepository;
    private $config;
    private $cache;
    private $serializer;

    public function __construct(
        CategoryCollectionFactory $categoryCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        ConfigRepository $configRepository,
        Config $config,
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->productCollectionFactory = $productCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->configRepository = $configRepository;
        $this->config = $config;
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    public function getMainCategories(?int $storeId = null, bool $includeDisabled = false): array
    {
        $storeId = $this->resolveStoreId($storeId);
        $cacheId = self::CACHE_PREFIX . 'main_' . $storeId . '_' . ((int)$includeDisabled);
        $cached = $this->cache->load($cacheId);
        if (is_string($cached) && $cached !== '') {
            try {
                $data = $this->serializer->unserialize($cached);
                if (is_array($data)) {
                    return $data;
                }
            } catch (\Throwable $e) {
            }
        }

        $categories = [];
        foreach ($this->loadTopLevelCategories($storeId) as $category) {
            $config = $this->configRepository->getCategoryConfig((int)$category->getId());
            if (!$includeDisabled && !$this->isCategoryVisibleAndEnabled($category, $config, $storeId)) {
                continue;
            }

            $categories[] = $this->buildSidebarItem($category, $storeId);
        }

        $this->cache->save($this->serializer->serialize($categories), $cacheId, [self::CACHE_TAG], max(60, $this->config->getCacheLifetime($storeId)));
        return $categories;
    }

    public function getShowcasePayload(int $categoryId, ?int $storeId = null, bool $includeDisabled = false): array
    {
        $storeId = $this->resolveStoreId($storeId);
        if ($categoryId <= 0) {
            $main = $this->getMainCategories($storeId, $includeDisabled);
            $categoryId = (int)($main[0]['category_id'] ?? 0);
        }

        $cacheId = self::CACHE_PREFIX . 'payload_' . $storeId . '_' . $categoryId . '_' . ((int)$includeDisabled);
        $cached = $this->cache->load($cacheId);
        if (is_string($cached) && $cached !== '') {
            try {
                $data = $this->serializer->unserialize($cached);
                if (is_array($data)) {
                    return $data;
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            $category = $this->categoryRepository->get($categoryId, $storeId);
        } catch (NoSuchEntityException $e) {
            return [
                'success' => false,
                'message' => __('Category not found.'),
                'category_id' => $categoryId,
                'sidebar' => $this->getMainCategories($storeId, $includeDisabled),
                'groups' => [],
            ];
        }

        $payload = $this->buildCategoryPayload($category, $storeId, $includeDisabled);
        $payload['success'] = true;
        $payload['sidebar'] = $this->getMainCategories($storeId, $includeDisabled);

        $this->cache->save($this->serializer->serialize($payload), $cacheId, [self::CACHE_TAG, 'catalog_category_' . $categoryId], max(60, $this->config->getCacheLifetime($storeId)));
        return $payload;
    }

    public function clearCacheByCategoryId(int $categoryId): void
    {
        $this->cache->clean([self::CACHE_TAG, 'catalog_category_' . $categoryId]);
    }

    private function buildSidebarItem(CategoryInterface $category, int $storeId): array
    {
        $config = $this->configRepository->getCategoryConfig((int)$category->getId());
        return [
            'category_id' => (int)$category->getId(),
            'entity_id' => (int)($config['entity_id'] ?? 0),
            'label' => $this->resolveLabel($category, $config),
            'image' => $this->resolveImage($category, $config, 'category_image', $storeId),
            'banner' => $this->resolveImage($category, $config, 'banner_image', $storeId),
            'banner_link' => (string)($config['banner_link'] ?? ''),
            'url' => (string)$category->getUrl(),
            'sort_order' => (int)($config['sort_order'] ?? $category->getPosition()),
            'is_enabled' => (int)($config['is_enabled'] ?? 1),
            'config' => $config,
        ];
    }

    private function buildCategoryPayload(CategoryInterface $category, int $storeId, bool $includeDisabled = false): array
    {
        $config = $this->configRepository->getCategoryConfig((int)$category->getId());
        $groups = [];
        $manualByGroup = [];

        foreach ($this->configRepository->getManualCategories((int)$category->getId()) as $manualRow) {
            $groupId = (int)($manualRow['group_category_id'] ?? 0);
            if (!isset($manualByGroup[$groupId])) {
                $manualByGroup[$groupId] = [];
            }

            if (!$includeDisabled && (int)$manualRow['is_enabled'] !== 1) {
                continue;
            }

            $manualByGroup[$groupId][] = [
                'entity_id' => (int)$manualRow['entity_id'],
                'category_id' => 0,
                'label' => (string)$manualRow['title'],
                'image' => $this->resolveManualImage((string)$manualRow['image'], $storeId),
                'banner' => '',
                'banner_link' => '',
                'url' => (string)$manualRow['url'],
                'sort_order' => (int)$manualRow['sort_order'],
                'is_enabled' => (int)$manualRow['is_enabled'],
                'manual' => true,
                'manual_data' => $manualRow,
            ];
        }

        $manualGroups = $this->configRepository->getManualGroups((int)$category->getId());
        foreach ($manualGroups as $manualGroup) {
            if (!$includeDisabled && (int)$manualGroup['is_enabled'] !== 1) {
                continue;
            }

            $manualGroupId = (int)$manualGroup['entity_id'];
            $groupManualItems = $manualByGroup[-$manualGroupId] ?? [];

            $groups[] = [
                'heading_category_id' => -$manualGroupId,
                'heading' => (string)$manualGroup['label'],
                'sort_order' => (int)$manualGroup['sort_order'],
                'is_enabled' => (int)$manualGroup['is_enabled'],
                'is_manual_group' => true,
                'manual_group_id' => $manualGroupId,
                'items' => $groupManualItems,
            ];
        }

        foreach ($this->loadDirectChildren((int)$category->getId(), $storeId) as $child) {
            $childConfig = $this->configRepository->getCategoryConfig((int)$child->getId());
            if (!$includeDisabled && !$this->isCategoryVisibleAndEnabled($child, $childConfig, $storeId)) {
                continue;
            }

            $items = [];
            foreach ($this->loadDirectChildren((int)$child->getId(), $storeId) as $grandChild) {
                $grandChildConfig = $this->configRepository->getCategoryConfig((int)$grandChild->getId());
                if (!$includeDisabled && !$this->isCategoryVisibleAndEnabled($grandChild, $grandChildConfig, $storeId)) {
                    continue;
                }

                $items[] = $this->buildCardPayload($grandChild, $grandChildConfig, $storeId, false);
            }

            $groupManualItems = $manualByGroup[(int)$child->getId()] ?? [];
            if (!empty($groupManualItems)) {
                $items = array_merge($items, $groupManualItems);
            }

            if (!$includeDisabled && empty($items)) {
                $items = [];
            }

            usort($items, static function (array $left, array $right): int {
                $sortCompare = ((int)($left['sort_order'] ?? 0)) <=> ((int)($right['sort_order'] ?? 0));
                if ($sortCompare !== 0) {
                    return $sortCompare;
                }

                return strcmp((string)($left['label'] ?? ''), (string)($right['label'] ?? ''));
            });

            $groups[] = [
                'heading_category_id' => (int)$child->getId(),
                'heading' => $this->resolveLabel($child, $childConfig),
                'sort_order' => (int)$child->getPosition(),
                'is_enabled' => (int)($childConfig['is_enabled'] ?? 1),
                'items' => $items,
            ];
        }

        $manualItems = $manualByGroup[0] ?? [];

        if (!empty($manualItems)) {
            $groups[] = [
                'heading_category_id' => 0,
                'heading' => __('Manual Categories'),
                'sort_order' => 999,
                'is_enabled' => 1,
                'items' => $manualItems,
            ];
        }

        usort($groups, static function (array $left, array $right): int {
            return ((int)($left['sort_order'] ?? 0)) <=> ((int)($right['sort_order'] ?? 0));
        });

        // var_dump($config['banner_link']);die;
        $categoryUrl = $this->storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK) . $category->getUrlPath() . '.html';

        return [
            'category_id' => (int)$category->getId(),
            'config' => $config,
            'label' => $this->resolveLabel($category, $config),
            'banner' => $this->resolveImage($category, $config, 'banner_image', $storeId),
            'banner_link' => (string)(isset($config['banner_link']) ? $config['banner_link'] : $categoryUrl),
            'image' => $this->resolveImage($category, $config, 'category_image', $storeId),
            'groups' => $groups,
        ];
    }

    private function buildCardPayload(CategoryInterface $category, array $config, int $storeId, bool $manual): array
    {
        return [
            'entity_id' => (int)($config['entity_id'] ?? 0),
            'category_id' => (int)$category->getId(),
            'label' => $this->resolveLabel($category, $config),
            'image' => $this->resolveImage($category, $config, 'category_image', $storeId),
            'banner' => $this->resolveImage($category, $config, 'banner_image', $storeId),
            'banner_link' => (string)($config['banner_link'] ?? ''),
            'url' => (string)$category->getUrl(),
            'sort_order' => (int)($config['sort_order'] ?? $category->getPosition()),
            'is_enabled' => (int)($config['is_enabled'] ?? 1),
            'config' => $config,
            'manual' => $manual,
        ];
    }

    private function loadTopLevelCategories(int $storeId): array
    {
        $store = $this->storeManager->getStore($storeId);
        $rootId = (int)$store->getRootCategoryId();
        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect(['name', 'image', 'thumbnail', 'is_active', 'include_in_menu', 'url_key', 'url_path', 'position']);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->addAttributeToFilter('include_in_menu', 1);
        $collection->addAttributeToFilter('parent_id', $rootId);
        $collection->setOrder('position', 'ASC');

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }

        return $items;
    }

    private function loadDirectChildren(int $parentId, int $storeId): array
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->setStoreId($storeId);
        $collection->addAttributeToSelect(['name', 'image', 'thumbnail', 'is_active', 'include_in_menu', 'url_key', 'url_path', 'position']);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->addAttributeToFilter('include_in_menu', 1);
        $collection->addAttributeToFilter('parent_id', $parentId);
        $collection->setOrder('position', 'ASC');

        $items = [];
        foreach ($collection as $item) {
            $items[] = $item;
        }

        return $items;
    }

    private function resolveLabel(CategoryInterface $category, array $config): string
    {
        $label = trim((string)($config['custom_label'] ?? ''));
        return $label !== '' ? $label : (string)$category->getName();
    }

    private function isCategoryVisibleAndEnabled(CategoryInterface $category, array $config, int $storeId): bool
    {
        $isActive = (int)$category->getData('is_active') === 1;
        $inMenu = (int)$category->getData('include_in_menu') === 1;
        $enabled = (int)($config['is_enabled'] ?? 1) === 1;

        return $isActive && $inMenu && $enabled;
    }

    private function resolveImage(CategoryInterface $category, array $config, string $configKey, int $storeId): string
    {
        if (!$this->config->enableImages($storeId)) {
            return $this->resolvePlaceholderImage($storeId);
        }

        $configured = trim((string)($config[$configKey] ?? ''));
        if ($configured !== '') {
            return $this->normalizeMediaPath($configured, $storeId);
        }

        $categoryImage = trim((string)$category->getData('image'));
        if ($categoryImage !== '') {
            return $this->buildMediaUrl($categoryImage, $storeId);
        }

        // Only use product image fallback for card/category_image, not for banner_image
        if ($configKey === 'category_image') {
            $productImage = $this->getFirstProductImageForCategory((int)$category->getId(), $storeId);
            if ($productImage) {
                return $productImage;
            }
        }

        return $this->resolvePlaceholderImage($storeId);

    }

    /**
     * Get the image URL of the first enabled product in a category
     * 13200
     * 12500
     * 5222
     * 3370
     * 700
     */
    private function getFirstProductImageForCategory(int $categoryId, int $storeId): ?string
    {
        try {
            $collection = $this->productCollectionFactory->create();
            $collection->addAttributeToSelect('image');
            $collection->addCategoriesFilter(['in' => [$categoryId]]);
            $collection->addAttributeToFilter('status', 1);
            $collection->addAttributeToFilter('visibility', ['neq' => 1]);
            $collection->setPageSize(1);
            $collection->setCurPage(1);
            $collection->setStoreId($storeId);
            $product = $collection->getFirstItem();
            if ($product && $product->getId() && $product->getImage() && $product->getImage() !== 'no_selection') {
                return $this->buildMediaUrl('catalog/product' . $product->getImage(), $storeId);
            }
        } catch (\Throwable $e) {}
        return null;
    }
    private function resolveManualImage(string $path, int $storeId): string
    {
        if (!$this->config->enableImages($storeId)) {
            return $this->resolvePlaceholderImage($storeId);
        }

        $path = trim($path);
        if ($path === '') {
            return $this->resolvePlaceholderImage($storeId);
        }

        return $this->buildMediaUrl($path, $storeId);
    }

    private function resolvePlaceholderImage(int $storeId): string
    {
        $placeholder = $this->config->getPlaceholderImage($storeId);
        return $placeholder !== '' ? $this->buildMediaUrl($placeholder, $storeId) : '';
    }

    private function normalizeMediaPath(string $path, int $storeId): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }

        return $this->buildMediaUrl($path, $storeId);
    }

    private function buildMediaUrl(string $path, int $storeId): string
    {
        $path = trim($path);
        if ($path === '') {
            return '';
        }

        if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0) {
            return $path;
        }

        $path = ltrim($path, '/');
        if (strpos($path, 'media/') === 0) {
            $path = substr($path, 6);
        }

        $mediaBaseUrl = rtrim((string)$this->storeManager->getStore($storeId)->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA), '/');
        return $mediaBaseUrl . '/' . ltrim($path, '/');
    }

    private function resolveStoreId(?int $storeId): int
    {
        if ($storeId !== null && $storeId > 0) {
            return $storeId;
        }

        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
