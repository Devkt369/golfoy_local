<?php

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Api\Data\QuestionOptionInterface;
use Magento\Framework\Model\AbstractModel;

class QuestionOption extends AbstractModel implements QuestionOptionInterface
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'artm_customersurvey_question_option';

    protected function _construct()
    {
        $this->_init(\ArtM\CustomerSurvey\Model\ResourceModel\QuestionOption::class);
    }

    public function getOptionId()
    {
        return (int)$this->getData(self::OPTION_ID);
    }

    public function setOptionId($optionId)
    {
        return $this->setData(self::OPTION_ID, $optionId);
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

    public function getValue()
    {
        return (string)$this->getData(self::VALUE);
    }

    public function setValue($value)
    {
        return $this->setData(self::VALUE, $value);
    }

    public function getImage()
    {
        return (string)$this->getData(self::IMAGE);
    }

    public function setImage($image)
    {
        return $this->setData(self::IMAGE, $image);
    }

    public function getSortOrder()
    {
        return (int)$this->getData(self::SORT_ORDER);
    }

    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }
}
