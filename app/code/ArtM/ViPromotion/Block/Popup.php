<?php

namespace ArtM\ViPromotion\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use ArtM\ViPromotion\Helper\Data;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class Popup extends Template
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    public function __construct(
        Context $context,
        Data $helper,
        Registry $registry,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
    }

    /**
     * Check if popup should be displayed
     * @return bool
     */
    public function shouldShowPopup()
    {
        // Show popup if login observer registered it OR if it should be shown based on helper logic
        return $this->registry->registry('vipromotion_show_popup') === true || 
               $this->helper->shouldShowPopup();
    }

    /**
     * Get popup title
     * @return string
     */
    public function getPopupTitle()
    {
        return $this->helper->getPopupTitle();
    }

    /**
     * Get popup content
     * @return string
     */
    public function getPopupContent()
    {
        $content = $this->helper->getPopupContent();
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