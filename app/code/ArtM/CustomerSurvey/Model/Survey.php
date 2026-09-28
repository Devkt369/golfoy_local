<?php

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Api\Data\SurveyInterface;
use Magento\Framework\Model\AbstractModel;

class Survey extends AbstractModel implements SurveyInterface
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_DISABLED = 'disabled';

    public const DISPLAY_TYPE_INLINE = 'inline';
    public const DISPLAY_TYPE_POPUP = 'popup';

    public const INLINE_POSITION_BEFORE = 'before';
    public const INLINE_POSITION_AFTER = 'after';
    public const INLINE_POSITION_INSIDE = 'inside';
    public const INLINE_POSITION_REPLACE = 'replace';

    public const POPUP_TRIGGER_PAGE_LOAD = 'page_load';
    public const POPUP_TRIGGER_AFTER_SECONDS = 'after_seconds';
    public const POPUP_TRIGGER_AFTER_PAGE_VIEWS = 'after_page_views';
    public const POPUP_TRIGGER_ON_LOGIN = 'on_login';
    public const POPUP_TRIGGER_ON_LOGOUT = 'on_logout';
    public const POPUP_TRIGGER_ON_ADD_TO_CART = 'on_add_to_cart';
    public const POPUP_TRIGGER_ON_CART_PAGE = 'on_cart_page';
    public const POPUP_TRIGGER_ON_CHECKOUT_PAGE = 'on_checkout_page';
    public const POPUP_TRIGGER_ON_ORDER_SUCCESS = 'on_order_success';
    public const POPUP_TRIGGER_EXIT_INTENT = 'exit_intent';

    public const FREQ_NOT_ANSWERED_EVERY_VISIT = 'every_visit';
    public const FREQ_NOT_ANSWERED_ONCE_NEVER = 'once_never';
    public const FREQ_NOT_ANSWERED_AFTER_DAYS = 'after_days';

    public const FREQ_ANSWERED_NEVER = 'never';
    public const FREQ_ANSWERED_AFTER_DAYS = 'after_days';

    /**
     * @var string
     */
    protected $_eventPrefix = 'artm_customersurvey_survey';

    protected function _construct()
    {
        $this->_init(\ArtM\CustomerSurvey\Model\ResourceModel\Survey::class);
    }

    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_ACTIVE,
            self::STATUS_DISABLED,
        ];
    }

    public static function getAvailableDisplayTypes(): array
    {
        return [
            self::DISPLAY_TYPE_INLINE,
            self::DISPLAY_TYPE_POPUP,
        ];
    }

    public static function getAvailableInlinePositions(): array
    {
        return [
            self::INLINE_POSITION_BEFORE,
            self::INLINE_POSITION_AFTER,
            self::INLINE_POSITION_INSIDE,
            self::INLINE_POSITION_REPLACE,
        ];
    }

    public static function getAvailablePopupTriggers(): array
    {
        return [
            self::POPUP_TRIGGER_PAGE_LOAD,
            self::POPUP_TRIGGER_AFTER_SECONDS,
            self::POPUP_TRIGGER_AFTER_PAGE_VIEWS,
            self::POPUP_TRIGGER_ON_LOGIN,
            self::POPUP_TRIGGER_ON_LOGOUT,
            self::POPUP_TRIGGER_ON_ADD_TO_CART,
            self::POPUP_TRIGGER_ON_CART_PAGE,
            self::POPUP_TRIGGER_ON_CHECKOUT_PAGE,
            self::POPUP_TRIGGER_ON_ORDER_SUCCESS,
            self::POPUP_TRIGGER_EXIT_INTENT,
        ];
    }

    public static function getAvailableNotAnsweredFrequencies(): array
    {
        return [
            self::FREQ_NOT_ANSWERED_EVERY_VISIT,
            self::FREQ_NOT_ANSWERED_ONCE_NEVER,
            self::FREQ_NOT_ANSWERED_AFTER_DAYS,
        ];
    }

    public static function getAvailableAnsweredFrequencies(): array
    {
        return [
            self::FREQ_ANSWERED_NEVER,
            self::FREQ_ANSWERED_AFTER_DAYS,
        ];
    }

    public function getSurveyId()
    {
        return (int)$this->getData(self::SURVEY_ID);
    }

    public function setSurveyId($surveyId)
    {
        return $this->setData(self::SURVEY_ID, $surveyId);
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

    public function getStatus()
    {
        return (string)$this->getData(self::STATUS);
    }

    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getPriority()
    {
        return (int)$this->getData(self::PRIORITY);
    }

    public function setPriority($priority)
    {
        return $this->setData(self::PRIORITY, $priority);
    }

    public function getSortOrder()
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    public function getStartDate()
    {
        return $this->getData(self::START_DATE);
    }

    public function setStartDate($startDate)
    {
        return $this->setData(self::START_DATE, $startDate);
    }

    public function getEndDate()
    {
        return $this->getData(self::END_DATE);
    }

    public function setEndDate($endDate)
    {
        return $this->setData(self::END_DATE, $endDate);
    }

    public function getCustomerGroupIds()
    {
        return (string)$this->getData(self::CUSTOMER_GROUP_IDS);
    }

    public function setCustomerGroupIds($customerGroupIds)
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, $customerGroupIds);
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
