<?php
/*
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\SecondDisplay\Plugin;

/**
 * Plugin Second Display
 */
class SecondDisplayPlugin
{
    const XML_PATH_ENABLE = 'webpos/second_display/second_display_enable';
    const XML_PATH_IMAGE = 'webpos/second_display/second_display_image';

    /**
     * @var \Magestore\Webpos\Api\Data\Config\ConfigExtensionFactory
     */
    public $configExtensionFactory;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    public $scopeConfig;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    public $storeManager;

    /**
     * SecondDisplayPlugin constructor.
     *
     * @param \Magestore\Webpos\Api\Data\Config\ConfigExtensionFactory $configExtensionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magestore\Webpos\Api\Data\Config\ConfigExtensionFactory $configExtensionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface       $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface               $storeManager
    ) {
        $this->configExtensionFactory = $configExtensionFactory;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Plugin after get config in getAllConfig
     *
     * @param \Magestore\Webpos\Model\Config\ConfigRepository $subject
     * @param \Magestore\Webpos\Api\Data\Config\ConfigInterface $result
     * @return \Magestore\Webpos\Api\Data\Config\ConfigInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetAllConfig(
        \Magestore\Webpos\Model\Config\ConfigRepository   $subject,
        \Magestore\Webpos\Api\Data\Config\ConfigInterface $result
    ) {
        $extensionAttributes = $result->getExtensionAttributes();
        if (!$extensionAttributes) {
            $extensionAttributes = $this->configExtensionFactory->create();
        }
        // get enable value
        $enable = $this->scopeConfig->getValue(self::XML_PATH_ENABLE);
        $extensionAttributes->setSecondDisplayEnable($enable);
        // get image
        $image = $this->scopeConfig->getValue(self::XML_PATH_IMAGE);
        if ($image) {
            $media = $this->storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            $imageUrl = $media . 'second_display/image/' . $image;
            $extensionAttributes->setSecondDisplayImage($imageUrl);
        }
        $result->setExtensionAttributes($extensionAttributes);
        return $result;
    }
}
