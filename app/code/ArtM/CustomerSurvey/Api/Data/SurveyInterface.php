<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface SurveyInterface
{
    public const SURVEY_ID = 'survey_id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const STATUS = 'status';
    public const PRIORITY = 'priority';
    public const SORT_ORDER = 'sort_order';
    public const START_DATE = 'start_date';
    public const END_DATE = 'end_date';
    public const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getSurveyId();

    public function setSurveyId($surveyId);

    public function getName();

    public function setName($name);

    public function getDescription();

    public function setDescription($description);

    public function getStatus();

    public function setStatus($status);

    public function getPriority();

    public function setPriority($priority);

    public function getSortOrder();

    public function setSortOrder($sortOrder);

    public function getStartDate();

    public function setStartDate($startDate);

    public function getEndDate();

    public function setEndDate($endDate);

    public function getCustomerGroupIds();

    public function setCustomerGroupIds($customerGroupIds);

    public function getCreatedAt();

    public function setCreatedAt($createdAt);

    public function getUpdatedAt();

    public function setUpdatedAt($updatedAt);
}
