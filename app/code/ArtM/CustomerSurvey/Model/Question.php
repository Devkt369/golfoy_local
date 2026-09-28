<?php

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Api\Data\QuestionInterface;
use Magento\Framework\Model\AbstractModel;

class Question extends AbstractModel implements QuestionInterface
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_RADIO = 'radio';
    public const TYPE_CHECKBOX = 'checkbox';
    public const TYPE_DROPDOWN = 'dropdown';
    public const TYPE_RATING = 'rating';
    public const TYPE_YES_NO = 'yes_no';
    public const TYPE_IMAGE_SELECTION = 'image_selection';

    /**
     * @var string
     */
    protected $_eventPrefix = 'artm_customersurvey_question';

    protected function _construct()
    {
        $this->_init(\ArtM\CustomerSurvey\Model\ResourceModel\Question::class);
    }

    public static function getAvailableTypes(): array
    {
        return [
            self::TYPE_TEXT,
            self::TYPE_TEXTAREA,
            self::TYPE_RADIO,
            self::TYPE_CHECKBOX,
            self::TYPE_DROPDOWN,
            self::TYPE_RATING,
            self::TYPE_YES_NO,
            self::TYPE_IMAGE_SELECTION,
        ];
    }

    public static function getOptionBasedTypes(): array
    {
        return [
            self::TYPE_RADIO,
            self::TYPE_CHECKBOX,
            self::TYPE_DROPDOWN,
            self::TYPE_IMAGE_SELECTION,
        ];
    }

    public function getQuestionId()
    {
        return (int)$this->getData(self::QUESTION_ID);
    }

    public function setQuestionId($questionId)
    {
        return $this->setData(self::QUESTION_ID, $questionId);
    }

    public function getTitle()
    {
        return (string)$this->getData(self::TITLE);
    }

    public function setTitle($title)
    {
        return $this->setData(self::TITLE, $title);
    }

    public function getDescription()
    {
        return (string)$this->getData(self::DESCRIPTION);
    }

    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    public function getType()
    {
        return (string)$this->getData(self::TYPE);
    }

    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    public function getIsRequired()
    {
        return (int)$this->getData(self::IS_REQUIRED);
    }

    public function setIsRequired($isRequired)
    {
        return $this->setData(self::IS_REQUIRED, $isRequired);
    }

    public function getMinSelection()
    {
        $value = $this->getData(self::MIN_SELECTION);
        return $value === null ? null : (int)$value;
    }

    public function setMinSelection($minSelection)
    {
        return $this->setData(self::MIN_SELECTION, $minSelection);
    }

    public function getMaxSelection()
    {
        $value = $this->getData(self::MAX_SELECTION);
        return $value === null ? null : (int)$value;
    }

    public function setMaxSelection($maxSelection)
    {
        return $this->setData(self::MAX_SELECTION, $maxSelection);
    }

    public function getSortOrder()
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
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
