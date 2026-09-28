<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Helper;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class Data
 *
 * Use for helper data
 */
class Data extends AbstractHelper
{
    const CLICK_AND_COLLECT_ENABLE_CONFIG_XML_PATH = 'carriers/clickandcollect/active';
    const GOOGLE_API_WEBPOS_XML_PATH = 'webpos/general/google_api_key';
    const IN_STORE_DELIVERY_MAGENTO_XML_PATH = 'carriers/instore/active';
    const SEARCH_RADIUS_XML_PATH = 'click_and_collect/general/search_radius';
    const SHOW_STORE_LIST_ON_FRONTEND_CONFIG_XML_PATH = 'click_and_collect/general/show_store_list_on_frontend';

    /**
     *
     * @var UrlInterface
     */
    protected $_backendUrlBuilder;

    /**
     * Data constructor.
     * @param Context $context
     * @param UrlInterface $backendUrlBuilder
     */
    public function __construct(
        Context $context,
        UrlInterface $backendUrlBuilder
    ) {
        parent::__construct($context);
        $this->_backendUrlBuilder = $backendUrlBuilder;
    }

    /**
     * Get backend url
     *
     * @param string $path
     * @param array $params
     * @return string
     */
    public function getBackendUrl($path, $params = [])
    {
        return $this->_backendUrlBuilder->getUrl($path, $params);
    }

    /**
     * Get Config Click And Collect Enabled
     *
     * @return int
     */
    public function getConfigEnableClickAndCollect(): int
    {
        return (int) $this->scopeConfig->getValue(self::CLICK_AND_COLLECT_ENABLE_CONFIG_XML_PATH);
    }

    /**
     * Get Config Click And Collect Enabled
     *
     * @return int
     */
    public function getConfigEnableInStoreDeliveryMagento(): int
    {
        return (int) $this->scopeConfig->getValue(self::IN_STORE_DELIVERY_MAGENTO_XML_PATH);
    }

    /**
     * Get Google API key
     *
     * @return mixed
     */
    public function getGoogleApiKey()
    {
        return $this->scopeConfig->getValue(self::GOOGLE_API_WEBPOS_XML_PATH);
    }

    /**
     * Get Default Radius
     *
     * @return int
     */
    public function getDefaultRadius()
    {
        return (int)$this->scopeConfig->getValue(self::SEARCH_RADIUS_XML_PATH);
    }

    /**
     * Get Distance Unit
     *
     * @return string
     */
    public function getDistanceUnit()
    {
        return 'Km';
    }

    /**
     * Get config show store list on Frontend
     *
     * @return int
     */
    public function getConfigShowStoreListOnFrontend(): int
    {
        return (int) $this->scopeConfig->getValue(self::SHOW_STORE_LIST_ON_FRONTEND_CONFIG_XML_PATH);
    }
}
