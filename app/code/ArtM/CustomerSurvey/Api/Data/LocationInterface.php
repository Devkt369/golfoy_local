<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface LocationInterface
{
    public const LOCATION_ID = 'location_id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const PAGE_TYPE = 'page_type';
    public const URL_PATTERN = 'url_pattern';
    public const CSS_SELECTOR = 'css_selector';
    public const POSITION = 'position';
    public const SCREENSHOT = 'screenshot';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getLocationId();

    public function setLocationId($locationId);

    public function getName();

    public function setName($name);

    public function getDescription();

    public function setDescription($description);

    public function getPageType();

    public function setPageType($pageType);

    public function getUrlPattern();

    public function setUrlPattern($urlPattern);

    public function getCssSelector();

    public function setCssSelector($cssSelector);

    public function getPosition();

    public function setPosition($position);

    public function getScreenshot();

    public function setScreenshot($screenshot);

    public function getStatus();

    public function setStatus($status);

    public function getCreatedAt();

    public function setCreatedAt($createdAt);

    public function getUpdatedAt();

    public function setUpdatedAt($updatedAt);
}
