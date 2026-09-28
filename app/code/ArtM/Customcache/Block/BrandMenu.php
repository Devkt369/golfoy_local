<?php

namespace ArtM\Customcache\Block;

use Magento\Framework\View\Element\Template;

class BrandMenu extends Template
{
    protected $helperData;

    public function __construct(
        Template\Context $context,
        \ArtM\Common\Helper\Data $helperData,
        array $data = []
    ) {
        $this->helperData = $helperData;
        parent::__construct($context, $data);
    }

    public function getMenuCacheData()
    {
        return $this->helperData->isAvailableMenuCache();
    }
    public function getPartnersMenuCacheData()
    {
        return $this->helperData->isAvailablePartnersMenuCache();
    }
}

