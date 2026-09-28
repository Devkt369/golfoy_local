<?php

namespace ArtM\ViPromotion\Block;

use Magento\Theme\Block\Html\Header\Logo as MagentoLogo;
use Magento\Framework\View\Element\Template\Context;
use ArtM\ViPromotion\Helper\Data;
use Magento\MediaStorage\Helper\File\Storage\Database;

class Logo extends MagentoLogo
{
    /**
     * @var Data
     */
    protected $helper;

    public function __construct(
        Context $context,
        Database $fileStorageHelper,
        Data $helper,
        array $data = []
    ) {
        parent::__construct($context, $fileStorageHelper, $data);
        $this->helper = $helper;
    }

    /**
     * Check if custom logo should be displayed
     * @return bool
     */
    public function shouldShowCustomLogo()
    {
        return $this->helper->isCustomerInTargetGroup() && $this->getCustomLogoUrl();
    }

    /**
     * Get custom logo URL
     * @return string|null
     */
    public function getCustomLogoUrl()
    {
        return $this->helper->getCustomLogoUrl();
    }

    /**
     * Get fallback logo URL for demonstration
     * @return string
     */
    public function getDemoLogoUrl()
    {
        return $this->getViewFileUrl('ArtM_ViPromotion::images/demo-logo.png');
    }
}