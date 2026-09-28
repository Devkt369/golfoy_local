<?php
/**
 * Class Conditions
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

use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

/**
 * Class Conditions
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Conditions extends Generic implements TabInterface
{
    /**
     * @var \Magento\Backend\Block\Widget\Form\Renderer\Fieldset
     */
    protected $rendererFieldset;

    /**
     * @var \Magento\Rule\Block\Conditions|\Sparsh\AutoRelatedProducts\Block\Conditions
     */
    protected $conditions;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $request;

    /**
     * Conditions constructor.
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Sparsh\AutoRelatedProducts\Block\Conditions $conditions
     * @param \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset
     * @param \Magento\Framework\App\RequestInterface $request
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Sparsh\AutoRelatedProducts\Block\Conditions $conditions,
        \Magento\Backend\Block\Widget\Form\Renderer\Fieldset $rendererFieldset,
        \Magento\Framework\App\RequestInterface $request,
        array $data = []
    ) {
        $this->rendererFieldset = $rendererFieldset;
        $this->conditions = $conditions;
        $this->request = $request;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Where To Display');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Where To Display');
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
     * Prepare form before rendering html
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
        if ($this->request->getParam('type') != 'category') {

            $renderer = $this->rendererFieldset->setTemplate(
                'Magento_CatalogRule::promo/fieldset.phtml'
            )->setNewChildUrl(
                $this->getUrl('sparsh_auto_related_products/rules/newConditionHtml/form/rule_conditions_fieldset')
            );

            $fieldset = $form->addFieldset(
                'conditions_fieldset',
                [
                    'legend' => __(
                        'Apply the rule only if the following conditions are met (leave blank for all products).'
                    )
                ]
            )->setRenderer(
                $renderer
            );

            $fieldset->addField(
                'conditions',
                'text',
                ['name' => 'conditions', 'label' => __('Conditions'), 'title' => __('Conditions')]
            )->setRule(
                $model
            )->setRenderer(
                $this->conditions
            );
        } else {
            $fieldset = $form->addFieldset(
                'categories_fieldset',
                ['legend' => __('Apply the rule only for following categories (leave blank for all categories).')]
            );

            $fieldset->addField(
                'category_ids',
                \Sparsh\AutoRelatedProducts\Block\Adminhtml\Chooser::class,
                [
                    'name' => 'category_ids',
                    'label' => __('Categories'),
                    'title' => __('Categories')
                ]
            );
        }
        $form->addValues($model->getData());
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
