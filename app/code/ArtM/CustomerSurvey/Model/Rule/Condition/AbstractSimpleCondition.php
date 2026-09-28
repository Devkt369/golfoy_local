<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Rule\Condition;

use Magento\Rule\Model\Condition\AbstractCondition;

abstract class AbstractSimpleCondition extends AbstractCondition
{
    /**
     * @var string[]
     */
    protected $attributeOptions = [];

    public function loadAttributeOptions()
    {
        $this->setAttributeOption($this->attributeOptions);
        return $this;
    }

    public function getInputType()
    {
        return 'string';
    }

    public function getValueElementType()
    {
        return 'text';
    }

    public function asHtml()
    {
        return $this->getTypeElementHtml()
            . __('%1 %2 %3', $this->getAttributeElementHtml(), $this->getOperatorElementHtml(), $this->getValueElementHtml())
            . $this->getRemoveLinkHtml();
    }

    /**
     * Admin-only placeholder until frontend evaluator is implemented.
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        return true;
    }
}
