<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model;

use ArtM\CustomerSurvey\Model\Rule\Condition\CombineFactory;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Rule\Model\AbstractModel;

class Rule extends AbstractModel
{
    /**
     * @var CombineFactory
     */
    private $conditionsFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        TimezoneInterface $localeDate,
        CombineFactory $conditionsFactory,
        array $data = []
    ) {
        $this->conditionsFactory = $conditionsFactory;
        parent::__construct($context, $registry, $formFactory, $localeDate, null, null, $data);
        $this->setIdFieldName('survey_id');
    }

    public function getConditionsInstance()
    {
        return $this->conditionsFactory->create();
    }

    public function getActionsInstance()
    {
        return $this->conditionsFactory->create();
    }
}
