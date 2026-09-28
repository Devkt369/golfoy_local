<?php
/**
 * Copyright © Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectFrontend\Block\ListStore;

use Magento\Framework\View\Element\Template;
use Magestore\ClickAndCollect\Helper\Data as HelperData;
use Magestore\ClickAndCollectFrontend\Helper\Image as ImageHelper;

/**
 * List store block
 */
class ListStoreBox extends Template
{
    /**
     * @var ImageHelper
     */
    protected $imageHelper;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * ListStoreBox constructor.
     * @param Template\Context $context
     * @param ImageHelper $imageHelper
     * @param HelperData $helperData
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ImageHelper $imageHelper,
        HelperData $helperData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->imageHelper = $imageHelper;
        $this->helperData = $helperData;
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
     * Get Helper Data
     *
     * @return HelperData
     */
    public function getHelperData()
    {
        return $this->helperData;
    }
}
