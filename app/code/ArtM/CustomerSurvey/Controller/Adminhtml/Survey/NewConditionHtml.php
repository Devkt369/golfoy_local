<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Controller\Adminhtml\Survey;

use Magento\Backend\App\Action;
use Magento\Rule\Model\Condition\AbstractCondition;
use Magento\Framework\Controller\Result\RawFactory;

class NewConditionHtml extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_CustomerSurvey::survey_save';

    /**
     * @var RawFactory
     */
    private $resultRawFactory;

    /**
     * @var \ArtM\CustomerSurvey\Model\Rule\Condition\CombineFactory
     */
    private $combineFactory;

    /**
     * @var \ArtM\CustomerSurvey\Model\RuleFactory
     */
    private $ruleFactory;

    public function __construct(
        Action\Context $context,
        RawFactory $resultRawFactory,
        \ArtM\CustomerSurvey\Model\Rule\Condition\CombineFactory $combineFactory,
        \ArtM\CustomerSurvey\Model\RuleFactory $ruleFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->combineFactory = $combineFactory;
        $this->ruleFactory = $ruleFactory;
    }

    public function execute()
    {
        $id = (string)$this->getRequest()->getParam('id');
        $type = str_replace('-', '/', (string)$this->getRequest()->getParam('type'));
        $form = (string)$this->getRequest()->getParam('form', 'rule_conditions_fieldset');

        $condition = $this->combineFactory->create();
        if ($type) {
            $condition = $this->_objectManager->create($type);
        }

        $condition->setId($id)
            ->setType($type)
            ->setRule($this->ruleFactory->create())
            ->setPrefix('conditions');

        if ($condition instanceof AbstractCondition) {
            $condition->setJsFormObject($form);
        }

        $html = $condition->asHtmlRecursive();

        return $this->resultRawFactory->create()->setContents($html);
    }
}