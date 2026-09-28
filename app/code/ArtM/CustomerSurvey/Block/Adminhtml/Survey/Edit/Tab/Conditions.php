<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Block\Adminhtml\Survey\Edit\Tab;

use ArtM\CustomerSurvey\Model\RuleFactory;
use ArtM\CustomerSurvey\Model\Survey;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Form\Renderer\Fieldset;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Rule\Block\Conditions as ConditionsRenderer;

class Conditions extends Generic
{
    /**
     * @var Fieldset
     */
    private $rendererFieldset;

    /**
     * @var ConditionsRenderer
     */
    private $conditionsRenderer;

    /**
     * @var RuleFactory
     */
    private $ruleFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        ConditionsRenderer $conditionsRenderer,
        Fieldset $rendererFieldset,
        RuleFactory $ruleFactory,
        array $data = []
    ) {
        $this->rendererFieldset = $rendererFieldset;
        $this->conditionsRenderer = $conditionsRenderer;
        $this->ruleFactory = $ruleFactory;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    protected function _prepareForm()
    {
        /** @var Survey|null $survey */
        $survey = $this->_coreRegistry->registry('artm_customersurvey_survey');
        if (!$survey) {
            return parent::_prepareForm();
        }

        $rule = $this->ruleFactory->create();
        $rule->setData('conditions_serialized', (string)$survey->getData('conditions_serialized'));
        $rule->getConditions();

        $formName = 'customersurvey_survey_form';
        $conditionsFieldSetId = $rule->getConditionsFieldSetId($formName);
        $newChildUrl = $this->getUrl(
            'customersurvey/survey/newConditionHtml/form/' . $conditionsFieldSetId,
            ['form_namespace' => $formName]
        );

        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('survey_rule_');

        $renderer = $this->getLayout()->createBlock(Fieldset::class);
        $renderer->setTemplate('Magento_CatalogRule::promo/fieldset.phtml')
            ->setNewChildUrl($newChildUrl)
            ->setFieldSetId($conditionsFieldSetId);

        $fieldset = $form->addFieldset(
            'conditions_fieldset',
            ['legend' => __('Apply survey only if the following conditions are met (leave blank for all).')]
        )->setRenderer($renderer);

        $fieldset->addField(
            'conditions',
            'text',
            [
                'name' => 'conditions',
                'label' => __('Conditions'),
                'title' => __('Conditions'),
                'required' => false,
                'data-form-part' => $formName,
            ]
        )->setRule($rule)->setRenderer($this->conditionsRenderer);

        $this->setConditionFormName($rule->getConditions(), $formName);
        $form->setValues($survey->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }

    /**
     * @throws LocalizedException
     */
    private function setConditionFormName(\Magento\Rule\Model\Condition\AbstractCondition $conditions, string $formName): void
    {
        $conditions->setFormName($formName);
        if ($conditions->getConditions() && is_array($conditions->getConditions())) {
            foreach ($conditions->getConditions() as $condition) {
                $this->setConditionFormName($condition, $formName);
            }
        }
    }
}
