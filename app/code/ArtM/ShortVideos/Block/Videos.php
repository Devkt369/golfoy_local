<?php
/**
 * ArtM ShortVideos Frontend Block
 *
 * @category  ArtM
 * @package   ArtM_ShortVideos
 * @author    ArtM
 * @copyright Copyright (c) 2025 ArtM
 */

namespace ArtM\ShortVideos\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use ArtM\ShortVideos\Model\ResourceModel\Video\CollectionFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Pricing\Helper\Data as PriceHelper;
use Magento\Review\Block\Product\ReviewRenderer;

class Videos extends Template
{
    /**
     * Should videos redirect directly?
     *
     * @return bool
     */
    public function isDirectLink()
    {
        // Accepts '1', 1, true as true; everything else as false
        $val = $this->getData('direct_link');
        return ($val === '1' || $val === 1 || $val === true);
    }
    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var PriceHelper
     */
    protected $priceHelper;

    /**
     * @var ReviewRenderer
     * 
     */
    protected $_reviewRenderer;

    /**
     * @param Context $context
     * @param CollectionFactory $collectionFactory
     * @param ProductRepositoryInterface $productRepository
     * @param PriceHelper $priceHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        ProductRepositoryInterface $productRepository,
        PriceHelper $priceHelper,
        ReviewRenderer $_reviewRenderer,
        array $data = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->productRepository = $productRepository;
        $this->priceHelper = $priceHelper;
        $this->_reviewRenderer = $_reviewRenderer;
        parent::__construct($context, $data);
    }

    /**
     * Get videos based on shortcode parameters
     *
     * @return \ArtM\ShortVideos\Model\ResourceModel\Video\Collection
     */
    public function getVideos()
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', 1); // Only enabled videos

        // Filter by categories
        if ($this->getData('categories')) {
            $categories = array_map('trim', explode(',', $this->getData('categories')));
            $collection->addFieldToFilter('category', ['in' => $categories]);
        }

        // Filter by specific video IDs
        if ($this->getData('videos')) {
            $videoIds = array_map('trim', explode(',', $this->getData('videos')));
            $collection->addFieldToFilter('video_id', ['in' => $videoIds]);
        }

        // Apply sorting
        $sort = $this->getData('sort') ?: 'sort_order';
        if ($sort === 'newest') {
            $collection->setOrder('created_at', 'DESC');
        } elseif ($sort === 'oldest') {
            $collection->setOrder('created_at', 'ASC');
        } else {
            $collection->setOrder('sort_order', 'ASC');
        }

        // Apply limit
        $limit = $this->getData('limit') ?: 10;
        $collection->setPageSize($limit);

        return $collection;
    }

    /**
     * Get section title
     *
     * @return string
     */
    public function getSectionTitle()
    {
        return $this->getData('title') ?: '';
    }

    /**
     * Check if product description should be shown
     *
     * @return bool
     */
    public function shouldShowDescription()
    {
        return (bool)$this->getData('show_description');
    }

    /**
     * Check if product page popup is enabled
     *
     * @return bool
     */
    public function isProductPageEnabled()
    {
        return (bool)$this->getData('product_page');
    }

    /**
     * Check if slider should show only title/description (not product details)
     *
     * @return bool
     */
    public function shouldShowTitleOnly()
    {
        return (bool)$this->getData('slider_title_only');
    }
    /**
     * Get video embed URL
     *
     * @param \ArtM\ShortVideos\Model\Video $video
     * @return string
     */
    public function getVideoEmbedUrl($video)
    {
        $videoUrl = $video->getVideoUrl();
        $videoType = $video->getVideoType();

        if ($videoType === 'instagram') {
            // Convert Instagram Reel URL to embed with captioned
            if (preg_match('/instagram\.com\/reel\/([^\/\?]+)/', $videoUrl, $matches)) {
                return 'https://www.instagram.com/p/' . $matches[1] . '/embed/captioned';
            }
            // Already an embed URL
            if (strpos($videoUrl, '/embed') !== false) {
                return $videoUrl;
            }
        } elseif ($videoType === 'youtube') {
            // Convert YouTube Short URL to embed
            if (preg_match('/youtube\.com\/shorts\/([^\/\?]+)/', $videoUrl, $matches)) {
                return 'https://www.youtube.com/embed/' . $matches[1];
            } elseif (preg_match('/youtu\.be\/([^\/\?]+)/', $videoUrl, $matches)) {
                return 'https://www.youtube.com/embed/' . $matches[1];
            } elseif (preg_match('/youtube\.com\/watch\?v=([^&]+)/', $videoUrl, $matches)) {
                return 'https://www.youtube.com/embed/' . $matches[1];
            }
            // Already an embed URL
            if (strpos($videoUrl, '/embed/') !== false) {
                return $videoUrl;
            }
        } elseif ($videoType === 'upload') {
            return $videoUrl; // Direct URL for uploaded videos
        }

        return $videoUrl;
    }

    /**
     * Get unique slider ID
     *
     * @return string
     */
    public function getSliderId()
    {
        return 'artm-video-slider-' . uniqid();
    }

    /**
     * Get product by ID
     *
     * @param int $productId
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct($productId)
    {
        try {
            return $this->productRepository->getById($productId);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get product URL
     *
     * @param \ArtM\ShortVideos\Model\Video $video
     * @return string|null
     */
    public function getVideoLinkUrl($video)
    {
        if ($video->getIsProductLink() && $video->getProductId()) {
            try {
                $product = $this->getProduct($video->getProductId());
                if ($product && $product->getId()) {
                    // Check if this specific video has popup enabled
                    $showPopup = $video->getShowProductPopup();
                    $url = $product->getProductUrl();
                    if ($showPopup && $url) {
                        // Check if URL already has query parameters
                        $separator = (strpos($url, '?') !== false) ? '&' : '?';
                        $url .= $separator . 'video=' . $video->getVideoId();
                    }
                    return $url;
                }
            } catch (\Exception $e) {
                // Fallback to direct URL if product can't be loaded
                $url = $this->getUrl('catalog/product/view', ['id' => $video->getProductId()]);
                $showPopup = $video->getShowProductPopup();
                if ($showPopup) {
                    $url .= '?video=' . $video->getVideoId();
                }
                return $url;
            }
        } elseif ($video->getLinkUrl()) {
            return $video->getLinkUrl();
        }
        return null;
    }

    /**
     * Format price
     *
     * @param float $price
     * @return string
     */
    public function formatPrice($price)
    {
        return $this->priceHelper->currency($price, true, false);
    }

    public function getProductReviewCount($product){
        return '';
    }
    
    public function getProductRatingHtml($product){
        return $this->_reviewRenderer->getReviewsSummaryHtml(
            $product,
            $templateType = 'short',
            $displayIfNoReviews = false
        );
    }
}
