<?php

namespace ArtM\CustomerSurvey\Api\Data;

interface QuestionOptionInterface
{
    public const OPTION_ID = 'option_id';
    public const QUESTION_ID = 'question_id';
    public const TITLE = 'title';
    public const VALUE = 'value';
    public const IMAGE = 'image';
    public const SORT_ORDER = 'sort_order';

    public function getOptionId();

    public function setOptionId($optionId);

    public function getQuestionId();

    public function setQuestionId($questionId);

    public function getTitle();

    public function setTitle($title);

    public function getValue();

    public function setValue($value);

    public function getImage();

    public function setImage($image);

    public function getSortOrder();

    public function setSortOrder($sortOrder);
}
