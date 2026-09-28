<?php

namespace ArtM\ViPromotion\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use ArtM\ViPromotion\Helper\Data;
use Magento\Store\Model\StoreManagerInterface;

class Banner extends Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        Context $context,
        Data $helper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->storeManager = $storeManager;
    }

    /**
     * Check if banner should be displayed
     * @return bool
     */
    public function shouldShowBanner()
    {
        return $this->helper->shouldShowBanner();
    }

    /**
     * Get banner content with processed media URLs
     * @return string
     */
    public function getBannerContent()
    {
        $content = $this->helper->getBannerContent();
        return $this->processMediaDirectives($content);
    }

    /**
     * Process media URL directives in content
     * @param string $content
     * @return string
     */
    protected function processMediaDirectives($content)
    {
        // Process {{media url='path/to/file'}} directives
        $pattern = '/\{\{media\s+url=[\'"]([^\'"]+)[\'"]\s*\}\}/';
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);
        
        return preg_replace_callback($pattern, function($matches) use ($mediaUrl) {
            return $mediaUrl . $matches[1];
        }, $content);
    }
}