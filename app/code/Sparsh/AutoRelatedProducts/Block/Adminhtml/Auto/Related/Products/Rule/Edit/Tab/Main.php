<?php
/**
 * Class Main
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Block\Adminhtml\Auto\Related\Products\Rule\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;
use Sparsh\AutoRelatedProducts\Ui\Component\Form\RuleStatus;
use Sparsh\AutoRelatedProducts\Ui\Component\Form\Position;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Model\ResourceModel\Group\Collection as CustomerGroup;

/**
 * Class Main
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Main extends Generic implements TabInterface
{
    /**
     * @var Store
     */
    protected $systemStore;

    /**
     * @var RuleStatus
     */
    protected $ruleStatus;

    /**
     * @var CustomerGroup
     */
    protected $customerGroup;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var Position
     */
    protected $position;

    /**
     * Main constructor.
     *
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Store $systemStore
     * @param CustomerGroup $customerGroup
     * @param Position $position
     * @param RuleStatus $ruleStatus
     * @param RequestInterface $request
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Store $systemStore,
        CustomerGroup $customerGroup,
        Position $position,
        RuleStatus $ruleStatus,
        RequestInterface $request,
        array $data = []
    ) {
        $this->systemStore = $systemStore;
        $this->position = $position;
        $this->ruleStatus = $ruleStatus;
        $this->customerGroup = $customerGroup;
        $this->request = $request;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Rule Information');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Rule Information');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return Generic
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('Sparsh_AutoRelatedProducts');

        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('rule_');

        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General Information')]);

        if ($model->getId()) {
            $fieldset->addField('rule_id', 'hidden', ['name' => 'rule_id']);
        }

        $fieldset->addField(
            'status',
            'select',
            [
                'label' => __('Status'),
                'title' => __('Status'),
                'name' => 'status',
                'required' => true,
                'values' => $this->ruleStatus->toOptionArray()
            ]
        );

        if (!$model->getId()) {
            $model->setData('status', RuleStatus::ENABLED);
        }

        $fieldset->addField(
            'rule_name',
            'text',
            [
                'name' => 'rule_name',
                'label' => __('Rule Name'),
                'title' => __('Rule Name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'rule_type',
            'hidden',
            [
                'name' => 'rule_type',
                'label' => __('Rule Type'),
                'title' => __('Rule Type'),
                'required' => true
            ]
        );

        if (!$model->getId()) {
            $model->setData('rule_type', $this->request->getParam('type'));
        }

        $fieldset->addField(
            'position',
            'select',
            [
                'name' => 'position',
                'label' => __('Position'),
                'title' => __('Position'),
                'values' => $this->position->toOptionArray(),
                'required' => true
            ]
        )->setAfterElementHtml(
            '
                <script>
                    require([
                         "jquery",
                    ], function($){
                        function sparshAutoRelatedProductsChangePositionEvent() {
                            if($("#rule_position").val() == "custom"){
                                $("#rule_display_mode").val(0);
                                $("#rule_display_mode").prop("disabled", true);
                                $("div .field-display_mode").hide();
                                $("#sparsh_auto_related_products_entity_snippet_code_fieldset").show();
                                
                                if($("#rule_rule_type").val() == \'product\' || $("#rule_rule_type").val() == \'category\') {
                                    $("li[data-ui-id=\'sparsh-auto-related-products-rule-edit-tabs-tab-item-conditions-section\']").hide();                                    
                                }
                            }else{
                                $("#rule_display_mode").prop("disabled", false);
                                $("div .field-display_mode").show();
                                $("#sparsh_auto_related_products_entity_snippet_code_fieldset").hide();
                                
                                if($("#rule_rule_type").val() == \'product\' || $("#rule_rule_type").val() == \'category\') {
                                    $("li[data-ui-id=\'sparsh-auto-related-products-rule-edit-tabs-tab-item-conditions-section\']").show();                                    
                                }                                
                            }
                            
                            if($("#rule_position").val() == "left_popup" || $("#rule_position").val() == "right_popup"){
                                $("#rule_no_of_products").val(1);
                                $("#rule_no_of_products").prop("disabled", true);
                            }else{
                                $("#rule_no_of_products").prop("disabled", false);
                            }
                        }
                        $(document).ready(function () {
                            sparshAutoRelatedProductsChangePositionEvent();
                            $("#rule_position").change(function() {
                                sparshAutoRelatedProductsChangePositionEvent();
                            })
                        });
                      });
               </script>
            '
        );

        $fieldset->addField(
            'store_ids',
            'multiselect',
            [
                'name' => 'store_ids',
                'label' => __('Store Views'),
                'title' => __('Store Views'),
                'values' => $this->systemStore->getStoreValuesForForm(false, true),
                'required' => true
            ]
        );

        $fieldset->addField(
            'customer_group_ids',
            'multiselect',
            [
                'name' => 'customer_group_ids',
                'label' => __('Customer Group'),
                'title' => __('Customer Group'),
                'values' => $this->customerGroup->toOptionArray(),
                'required' => true
            ]
        );

        $fieldset->addField(
            'start_date',
            'date',
            [
                'name' => 'start_date',
                'label' => __('From'),
                'title' => __('From'),
                'date_format' => 'MM/dd/Y',
                'class' => 'validate-date validate-date-range date-range-attribute-from',
                'required' => true
            ]
        );
        $fieldset->addField(
            'end_date',
            'date',
            [
                'name' => 'end_date',
                'label' => __('To'),
                'title' => __('To'),
                'date_format' => 'MM/dd/Y',
                'date_format' => 'MM/dd/Y',
                'class' => 'validate-date validate-date-range date-range-attribute-to'
            ]
        );

        $fieldset->addField(
            'priority',
            'text',
            [
                'name' => 'priority',
                'label' => __('Priority'),
                'class' => 'validate-number'
            ]
        );

        $form->setValues($model->getData());

        if ($model->isReadonly()) {
            foreach ($fieldset->getElements() as $element) {
                $element->setReadonly(true, true);
            }
        }

        $this->setForm($form);

        return parent::_prepareForm();
    }
}
