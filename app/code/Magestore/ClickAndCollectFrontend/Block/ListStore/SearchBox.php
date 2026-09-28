<?php

/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Block\ListStore;

use Magento\Framework\View\Element\Template;
use Magestore\ClickAndCollect\Helper\Data as HelperData;

/**
 * Block SearchBox
 */
class SearchBox extends Template
{
    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * SearchBox constructor.
     * @param Template\Context $context
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helperData = $helperData;
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
}
