<?php
namespace ArtM\Optimization\Api\Data;

interface ProductCacheInterface
{
    const ID = 'id';
    const PRODUCT_ID = 'product_id';
    const ATTRIBUTES_HTML = 'attibutes_html';
    const TYPE = 'type';
    const PRICE_HTML = 'price_html';
    const TIER_PRICE_HTML = 'tier_price_html';
    const SIZE_ROLE = 'size_role';
    const PRODUCT_TAG = 'product_tag';
    const LAST_MODIFY = 'last_modify';

    
    public function getId();

    public function getProductId();
    public function setProductId($productId);

    public function getAttributesHtml();
    public function setAttributesHtml($attributesHtml);

    public function getType();
    public function setType($type);

    public function getPriceHtml();
    public function setPriceHtml($priceHtml);

    public function getTierPriceHtml();
    public function setTierPriceHtml($tierPriceHtml);

    public function getSizeRole();
    public function setSizeRole($sizeRole);

    public function getProductTag();
    public function setProductTag($productTag);

    public function getLastModify();
    public function setLastModify($lastModify);
}