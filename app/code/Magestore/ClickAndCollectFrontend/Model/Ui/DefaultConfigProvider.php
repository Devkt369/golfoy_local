<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Asset\Repository as AssetRepo;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magestore\Webpos\Model\DistanceProvider\GoogleMap\GetApiKey;
use Psr\Log\LoggerInterface;

/**
 * Provide website for current store.
 */
class DefaultConfigProvider implements ConfigProviderInterface
{
    const XML_PATH_ALLOW_PICK_TIME = 'click_and_collect/general/allow_to_choose_collect_data_and_time';
    const XML_PATH_SEARCH_RADIUS = 'click_and_collect/general/search_radius';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var AssetRepo
     */
    private $assetRepo;

    /**
     * @var GetApiKey
     */
    private $getApiKey;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * DefaultConfigProvider constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param AssetRepo $assetRepo
     * @param GetApiKey $getApiKey
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param LoggerInterface $logger
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        AssetRepo $assetRepo,
        GetApiKey $getApiKey,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        LoggerInterface $logger
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->assetRepo = $assetRepo;
        $this->getApiKey = $getApiKey;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->logger = $logger;
    }

    /**
     * Returns current click and collect config
     *
     * @return array
     */
    public function getConfig()
    {
        try {
            $googleApiKey = $this->getApiKey->execute();
        } catch (LocalizedException $exception) {
            $googleApiKey = '';
        }
        return [
            'click_and_collect' => [
                'allow_to_choose_collect_data_and_time'=>
                    (int) $this->scopeConfig->getValue(self::XML_PATH_ALLOW_PICK_TIME),
                'search_radius'=>
                    (float) $this->scopeConfig->getValue(self::XML_PATH_SEARCH_RADIUS),
                'position_image_url' => $this->getPositionMarkSrc(),
                'googleMapApiKey' => $googleApiKey,
                'stockId' => $this->getStockId()
            ]
        ];
    }

    /**
     * Get the url to the position icon
     *
     * @return mixed
     */
    private function getPositionMarkSrc()
    {
        $fileId = 'Magestore_ClickAndCollectFrontend::images/position.jpg';
        return $this->assetRepo->getUrl($fileId);
    }

    /**
     * Get Stock Id
     *
     * @return int|null
     */
    public function getStockId()
    {
        try {
            $websiteCode = $this->storeManager->getWebsite()->getCode();
            return $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)->getStockId();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }
}
