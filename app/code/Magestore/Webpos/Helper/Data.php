<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Helper;

use Exception;
use Magento\Catalog\Model\Product\Type;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Filesystem;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\Giftvoucher\Model\Product\Type\Giftvoucher;
use Magestore\LoggerApi\Api\IsEnabledLogTypeInterface;
use Magestore\LoggerApi\Api\IsEnabledLogFlowInterface;
use Magestore\Webpos\Api\DataProvider\Session\GetSessionByIdInterface;
use Magestore\WebposIntegration\Controller\Rest\RequestProcessor;

/**
 * Class Data
 *
 * Used for helper data
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Data extends AbstractHelper
{
    const URL_TYPE_PWA = 'apps/pos';

    /**
     *
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    /**
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Data constructor.
     * @param Context $context
     * @param ObjectManagerInterface $objectManager
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->objectManager = $objectManager;
        $this->_storeManager = $storeManager;
    }

    /**
     * Get model
     *
     * @param string $modelName
     * @param array $arguments
     * @return mixed
     * @throws ValidatorException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getModel($modelName, array $arguments = [])
    {
        $model = $this->objectManager->create('\Magestore\Webpos\\' . $modelName);
        if (!$model) {
            throw new ValidatorException(
                __('%1 doesn\'t extends \Magento\Framework\Model\AbstractModel', $modelName)
            );
        }
        return $model;
    }

    /**
     * Get config
     *
     * @param string $path
     * @param int|null $storeId
     * @return string
     */
    public function getStoreConfig($path, $storeId = null)
    {
        if ($storeId == null) {
            return $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->getCurrentStoreView()->getId()
            );
        } else {
            return $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                (int) $storeId
            );
        }
    }

    /**
     * Get current location id
     *
     * @return mixed
     */
    public function getCurrentLocationId()
    {
        $objectManager = ObjectManager::getInstance();
        /** @var GetSessionByIdInterface $getSessionById */
        $getSessionById = $objectManager->get(GetSessionByIdInterface::class);
        $request = $objectManager->get(RequestInterface::class);
        try {
            $locationId = $request->getParam(\Magestore\Webpos\Model\Checkout\PosOrder::PARAM_ORDER_LOCATION_ID);
            if (!$locationId) {
                $session = $getSessionById->execute(
                    $request->getParam(RequestProcessor::SESSION_PARAM_KEY)
                );
                $locationId = $session->getLocationId();
            }
        } catch (Exception $e) {
            $locationId = $request->getParam(\Magestore\Webpos\Model\Checkout\PosOrder::PARAM_ORDER_LOCATION_ID);
        }
        return $locationId;
    }

    /**
     * Get webpos images
     *
     * @return string
     */
    public function getWebPosImages()
    {
        return $this->_storeManager->getStore()->getBaseUrl(
            UrlInterface::URL_TYPE_MEDIA
        ) . 'webpos/logo/' . $this->getWebPosLogoSetting();
    }

    /**
     * Get pos logo
     *
     * @return mixed
     */
    public function getWebPosLogoSetting()
    {
        return $this->scopeConfig->getValue(
            'webpos/general/webpos_logo',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if store credit enable
     *
     * @return bool
     */
    public function isStoreCreditEnable()
    {
        if ($this->_moduleManager->isEnabled('Magestore_Customercredit')) {
            if ($this->getStoreConfig('customercredit/general/enable')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if inventory success enable
     *
     * @return bool
     */
    public function isEnabledInventory()
    {
        return $this->_moduleManager->isEnabled('Magestore_InventorySuccess');
    }

    /**
     * Check if gift card enable
     *
     * @return bool
     */
    public function isEnabledGiftCard()
    {
        if ($this->_moduleManager->isEnabled('Magestore_Giftvoucher')) {
            if ($this->getStoreConfig('giftvoucher/general/active')) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if session enable
     *
     * @return bool
     */
    public function isEnableSession()
    {
        if ($this->getStoreConfig('webpos/session/enable')) {
            return true;
        }
        return false;
    }

    /**
     * Check if cash control enable
     *
     * @return bool
     */
    public function isCashControl()
    {
        if ($this->isEnableSession() &&
            $this->getStoreConfig('webpos/session/enable_cash_control')
        ) {
            return true;
        }
        return false;
    }

    /**
     * Get current store view
     *
     * @return StoreInterface
     */
    public function getCurrentStoreView()
    {
        /** @var Registry $registry */
        $registry = $this->objectManager->get(Registry::class);
        if ($registry->registry('pos_api_url')
            && strstr($registry->registry('pos_api_url'), 'rest//')) {
            // api route does not contain store view code
            try {
                return $this->_storeManager->getDefaultStoreView();
            } catch (Exception $e) {
                return $this->_storeManager->getStore();
            }
        }
        return $this->_storeManager->getStore();
    }

    /**
     * Get object manager
     *
     * @return ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->objectManager;
    }

    /**
     * Get pos url
     *
     * @return bool|string
     */
    public function getPosUrl()
    {
        $filesystem = $this->objectManager->create(Filesystem::class);
        $mediaUrl = $this->_storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_WEB)
            . $filesystem->getUri(DirectoryList::MEDIA);
        if (substr($mediaUrl, -strlen(UrlInterface::URL_TYPE_MEDIA . '/'))
            == UrlInterface::URL_TYPE_MEDIA . '/') {
            $parentUrl = substr(
                $mediaUrl,
                0,
                -strlen(UrlInterface::URL_TYPE_MEDIA . '/')
            );
        }
        if (substr($mediaUrl, -strlen(UrlInterface::URL_TYPE_MEDIA))
            == UrlInterface::URL_TYPE_MEDIA) {
            $parentUrl = substr($mediaUrl, 0, -strlen(UrlInterface::URL_TYPE_MEDIA));
        }
        $url = $parentUrl . self::URL_TYPE_PWA;
        return $url;
    }

    /**
     * Get pó relative url
     *
     * @return bool|mixed|string
     */
    public function getPosRelativeUrl()
    {
        $posUrl = $this->getPosUrl();
        $baseUrl = $this->_storeManager->getStore()->getBaseUrl();
        $relativeUrl = str_replace($baseUrl, '', $posUrl);
        return $relativeUrl;
    }

    /**
     * Is Enabled Elastic Search Engine
     *
     * @return boolean
     */
    public function isEnableElasticSearch()
    {
        if (strpos($this->scopeConfig->getValue('catalog/search/engine'), 'elasticsearch') !== false) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get product type ids to support
     *
     * @return array
     */
    public function getProductTypeIds()
    {
        $types = [
            Type::TYPE_VIRTUAL,
            Type::TYPE_SIMPLE,
            Grouped::TYPE_CODE,
            Type::TYPE_BUNDLE,
            Configurable::TYPE_CODE
        ];
        if ($this->isEnabledGiftCard()) {
            $types[] = Giftvoucher::GIFT_CARD_TYPE;
        }
        return $types;
    }

    /**
     * Check if barcode management enable
     *
     * @return bool
     */
    public function isEnabledBarcodeManagement()
    {
        return $this->_moduleManager->isEnabled('Magestore_BarcodeSuccess');
    }

    /**
     * Pre-check before process logic get data from log to increase performance when no log
     *
     * @param string $type
     * @param string $flow
     * @return bool
     */
    public function isEnabledLog(string $type, string $flow)
    {
        $hasLogModule = $this->_moduleManager->isEnabled('Magestore_LoggerApi');
        if ($hasLogModule) {
            return $this->objectManager->get(IsEnabledLogTypeInterface::class)->execute($type)
                && $this->objectManager->get(IsEnabledLogFlowInterface::class)->execute($flow);
        } else {
            return false;
        }
    }
}
