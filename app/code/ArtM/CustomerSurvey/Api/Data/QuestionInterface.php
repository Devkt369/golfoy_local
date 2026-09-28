<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface QuestionInterface
{
    public const QUESTION_ID = 'question_id';
    public const TITLE = 'title';
    public const DESCRIPTION = 'description';
    public const TYPE = 'type';
    public const IS_REQUIRED = 'is_required';
    public const MIN_SELECTION = 'min_selection';
    public const MAX_SELECTION = 'max_selection';
    public const SORT_ORDER = 'sort_order';
    public const STATUS = 'status';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public function getQuestionId();

    public function setQuestionId($questionId);

    public function getTitle();

    public function setTitle($title);

    public function getDescription();

    public function setDescription($description);

    public function getType();

    public function setType($type);

    public function getIsRequired();

    public function setIsRequired($isRequired);

    public function getMinSelection();

    public function setMinSelection($minSelection);

    public function getMaxSelection();

    public function setMaxSelection($maxSelection);

    public function getSortOrder();

    public function setSortOrder($sortOrder);

    public function getStatus();

    public function setStatus($status);

    public function getCreatedAt();

    public function setCreatedAt($createdAt);

    public function getUpdatedAt();

    public function setUpdatedAt($updatedAt);
}
