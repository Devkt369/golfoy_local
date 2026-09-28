<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Block;

use Magento\Framework\View\Element\Template;
use Magento\InventorySalesApi\Api\Data\SalesChannelInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magestore\ClickAndCollectFrontend\Helper\Image as ImageHelper;
use Magento\Framework\Serialize\Serializer\Json;
use Magestore\ClickAndCollect\Helper\Data as HelperData;

/**
 * Block Wrapper
 */
class Wrapper extends Template
{
    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var StockResolverInterface
     */
    protected $stockResolver;

    /**
     * Wrapper constructor.
     * @param Template\Context $context
     * @param ImageHelper $imageHelper
     * @param Json $json
     * @param HelperData $helperData
     * @param StockResolverInterface $stockResolver
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ImageHelper $imageHelper,
        Json $json,
        HelperData $helperData,
        StockResolverInterface $stockResolver,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->imageHelper = $imageHelper;
        $this->json = $json;
        $this->helperData = $helperData;
        $this->stockResolver = $stockResolver;
    }

    /**
     * Get media url of image.
     *
     * @param string $imagePath
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMediaUrlImage($imagePath = '')
    {
        return $this->imageHelper->getMediaUrlImage($imagePath);
    }

    /**
     * Get Marker Cluster Options
     *
     * @return bool|string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getMarkerClusterOptions()
    {
        $mcOptions = [
            [
                "height" => 53,
                "url" => $this->getMediaUrlImage('clickandcollect/markerclusterer/m1.png'),
                "width" => 53
            ],
            [
                "height" => 56,
                "url" => $this->getMediaUrlImage('clickandcollect/markerclusterer/m2.png'),
                "width" => 56
            ],
            [
                "height" => 66,
                "url" => $this->getMediaUrlImage('clickandcollect/markerclusterer/m3.png'),
                "width" => 66
            ],
            [
                "height" => 78,
                "url" => $this->getMediaUrlImage('clickandcollect/markerclusterer/m4.png'),
                "width" => 78
            ],
            [
                "height" => 90,
                "url" => $this->getMediaUrlImage('clickandcollect/markerclusterer/m5.png'),
                "width" => 90
            ]
        ];
        return $this->json->serialize($mcOptions);
    }

    /**
     * Get Helper Data
     *
     * @return HelperData
     */
    public function getHelperData()
    {
        return $this->helperData;
    }

    /**
     * Get Stock Id
     *
     * @return int|null
     */
    public function getStockId()
    {
        try {
            $websiteCode = $this->_storeManager->getWebsite()->getCode();
            return $this->stockResolver->execute(SalesChannelInterface::TYPE_WEBSITE, $websiteCode)->getStockId();
        } catch (\Exception $e) {
            $this->_logger->error($e->getMessage());
            return null;
        }
    }
}
