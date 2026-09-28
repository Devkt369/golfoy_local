<?php
namespace ArtM\CategoryPage\Block\Admin;

use ArtM\CategoryPage\Api\CategoryProviderInterface;
use ArtM\CategoryPage\Model\Config;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Store\Model\StoreManagerInterface;

class Page extends Template
{
    private $categoryProvider;
    private $config;
    private $storeManager;
    private $serializer;

    public function __construct(
        TemplateContext $context,
        CategoryProviderInterface $categoryProvider,
        Config $config,
        StoreManagerInterface $storeManager,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->categoryProvider = $categoryProvider;
        $this->config = $config;
        $this->storeManager = $storeManager;
        $this->serializer = $serializer;
    }

    public function getMode(): string
    {
        return (string)$this->getData('mode') ?: 'manage';
    }

    public function getSidebarCategories(): array
    {
        return $this->categoryProvider->getMainCategories($this->getStoreId(), true);
    }

    public function getPageConfig(): array
    {
        $sidebar = $this->getSidebarCategories();
        $initialId = (int)($sidebar[0]['category_id'] ?? 0);

        return [
            'mode' => $this->getMode(),
            'storeId' => $this->getStoreId(),
            'baseAdminUrl' => $this->getUrl('categorypage/'),
            'loadUrl' => $this->getUrl('categorypage/ajax/load'),
            'resetCategoryUrl' => $this->getUrl('categorypage/ajax/resetCategory'),
            'saveConfigUrl' => $this->getUrl('categorypage/ajax/saveConfig'),
            'saveManualUrl' => $this->getUrl('categorypage/ajax/saveManual'),
            'deleteManualGroupUrl' => $this->getUrl('categorypage/ajax/deleteManualGroup'),
            'saveSortUrl' => $this->getUrl('categorypage/ajax/saveSort'),
            'uploadImageUrl' => $this->getUrl('categorypage/ajax/uploadImage'),
            'settingsUrl' => $this->getUrl('adminhtml/system_config/edit/section/artm_categorypage'),
            'placeholderImage' => $this->config->getPlaceholderImage($this->getStoreId()),
            'sidebarWidth' => $this->config->getSidebarWidth($this->getStoreId()),
            'animationSpeed' => $this->config->getAnimationSpeed($this->getStoreId()),
            'initialCategoryId' => $initialId,
            'mainCategories' => $sidebar,
        ];
    }

    public function getPageConfigJson(): string
    {
        return $this->serializer->serialize($this->getPageConfig());
    }

    public function getStoreId(): int
    {
        try {
            return (int)$this->storeManager->getStore()->getId();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
