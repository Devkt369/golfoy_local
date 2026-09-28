<?php

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Api\Data\LocationInterface;
use Magento\Framework\Model\AbstractModel;

class Location extends AbstractModel implements LocationInterface
{
    public const PAGE_TYPE_PRODUCT = 'product';
    public const PAGE_TYPE_CATEGORY = 'category';
    public const PAGE_TYPE_CMS = 'cms';
    public const PAGE_TYPE_CART = 'cart';
    public const PAGE_TYPE_CHECKOUT = 'checkout';
    public const PAGE_TYPE_SUCCESS = 'success';
    public const PAGE_TYPE_CUSTOMER = 'customer';
    public const PAGE_TYPE_CUSTOM_URL = 'custom_url';

    public const POSITION_BEFORE = 'before';
    public const POSITION_AFTER = 'after';
    public const POSITION_INSIDE = 'inside';
    public const POSITION_REPLACE = 'replace';

    /**
     * @var string
     */
    protected $_eventPrefix = 'artm_customersurvey_location';

    protected function _construct()
    {
        $this->_init(\ArtM\CustomerSurvey\Model\ResourceModel\Location::class);
    }

    public static function getAvailablePageTypes(): array
    {
        return [
            self::PAGE_TYPE_PRODUCT,
            self::PAGE_TYPE_CATEGORY,
            self::PAGE_TYPE_CMS,
            self::PAGE_TYPE_CART,
            self::PAGE_TYPE_CHECKOUT,
            self::PAGE_TYPE_SUCCESS,
            self::PAGE_TYPE_CUSTOMER,
            self::PAGE_TYPE_CUSTOM_URL,
        ];
    }

    public static function getAvailablePositions(): array
    {
        return [
            self::POSITION_BEFORE,
            self::POSITION_AFTER,
            self::POSITION_INSIDE,
            self::POSITION_REPLACE,
        ];
    }

    public function getLocationId()
    {
        return (int)$this->getData(self::LOCATION_ID);
    }

    public function setLocationId($locationId)
    {
        return $this->setData(self::LOCATION_ID, $locationId);
    }

    public function getName()
    {
        return (string)$this->getData(self::NAME);
    }

    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    public function getDescription()
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getPageType()
    {
        return (string)$this->getData(self::PAGE_TYPE);
    }

    public function setPageType($pageType)
    {
        return $this->setData(self::PAGE_TYPE, $pageType);
    }

    public function getUrlPattern()
    {
        return (string)$this->getData(self::URL_PATTERN);
    }

    public function setUrlPattern($urlPattern)
    {
        return $this->setData(self::URL_PATTERN, $urlPattern);
    }

    public function getCssSelector()
    {
        return (string)$this->getData(self::CSS_SELECTOR);
    }

    public function setCssSelector($cssSelector)
    {
        return $this->setData(self::CSS_SELECTOR, $cssSelector);
    }

    public function getPosition()
    {
        return (string)$this->getData(self::POSITION);
    }

    public function setPosition($position)
    {
        return $this->setData(self::POSITION, $position);
    }

    public function getScreenshot()
    {
        return (string)$this->getData(self::SCREENSHOT);
    }

    public function setScreenshot($screenshot)
    {
        return $this->setData(self::SCREENSHOT, $screenshot);
    }

    public function getStatus()
    {
        return (int)$this->getData(self::STATUS);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getCreatedAt()
    {
        return (string)$this->getData(self::CREATED_AT);
    }

    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    public function getUpdatedAt()
    {
        return (string)$this->getData(self::UPDATED_AT);
    }

    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }
}
