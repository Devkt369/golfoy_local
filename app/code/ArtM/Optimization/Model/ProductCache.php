<?php
namespace ArtM\Optimization\Model;

use Magento\Framework\Model\AbstractModel;
use ArtM\Optimization\Api\Data\ProductCacheInterface;

class ProductCache extends AbstractModel implements ProductCacheInterface
{
    // Define constants for database field names
    const CACHE_ID = 'id';
    const PRODUCT_ID = 'product_id';
    const ATTRIBUTES_HTML = 'attributes_html';
    const TYPE = 'type';
    const PRICE_HTML = 'price_html';
    const TIER_PRICE_HTML = 'tier_price_html';
    const SIZE_ROLE = 'size_role';
    const PRODUCT_TAG = 'product_tag';
    const LAST_MODIFY = 'last_modify';

    protected function _construct()
    {
        $this->_init(\ArtM\Optimization\Model\ResourceModel\ProductCache::class);
    }

    // Implement getter and setter methods for each field using the defined constants

    public function getId()
    {
        return $this->getData(self::CACHE_ID);
    }

    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    public function setProductId($productId)
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getAttributesHtml()
    {
        return $this->getData(self::ATTRIBUTES_HTML);
    }

    public function setAttributesHtml($attributesHtml)
    {
        return $this->setData(self::ATTRIBUTES_HTML, $attributesHtml);
    }

    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getPriceHtml()
    {
        return $this->getData(self::PRICE_HTML);
    }

    public function setPriceHtml($priceHtml)
    {
        return $this->setData(self::PRICE_HTML, $priceHtml);
    }

    public function getTierPriceHtml()
    {
        return $this->getData(self::TIER_PRICE_HTML);
    }

    public function setTierPriceHtml($tierPriceHtml)
    {
        return $this->setData(self::TIER_PRICE_HTML, $tierPriceHtml);
    }

    public function getSizeRole()
    {
        return $this->getData(self::SIZE_ROLE);
    }

    public function setSizeRole($sizeRole)
    {
        return $this->setData(self::SIZE_ROLE, $sizeRole);
    }

    public function getProductTag()
    {
        return $this->getData(self::PRODUCT_TAG);
    }

    public function setProductTag($productTag)
    {
        return $this->setData(self::PRODUCT_TAG, $productTag);
    }

    public function getLastModify()
    {
        return $this->getData(self::LAST_MODIFY);
    }

    public function setLastModify($lastModify)
    {
        return $this->setData(self::LAST_MODIFY, $lastModify);
    }
}
