<?php
/**
 * Class Edit
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Controller\Adminhtml\Rules;

/**
 * Class Edit
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Edit extends \Sparsh\AutoRelatedProducts\Controller\Adminhtml\Rules\Rule
{
    /**
     * Edit action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->ruleFactory->create();

        if ($id) {
            $model->load($id);
            if (!$model->getRuleId()) {
                $this->messageManager->addErrorMessage(__('The rule no longer exists.'));
                $this->_redirect('sparsh_auto_related_products/rules/*');
                return;
            }
        }

        // set entered data if was error when we do save
        $data = $this->_session->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }

        $model->getConditions()->setFormName('sparsh_auto_related_products_rule_form');
        $model->getConditions()->setJsFormObject($model->getConditionsFieldSetId($model->getConditions()->getFormName()));
        $model->getActions()->setFormName('sparsh_auto_related_products_rule_form');
        $model->getActions()->setJsFormObject($model->getActionsFieldSetId($model->getActions()->getFormName()));
        $this->coreRegistry->register('Sparsh_AutoRelatedProducts', $model);

        $this->_initAction();
        $this->_view->getLayout()
            ->getBlock('sparsh_auto_related_products_rule_edit')
            ->setData('action', $this->getUrl('sparsh_auto_related_products/rules/save'));

        $this->_addBreadcrumb($id ? __('Edit Rule') : __('New Rule'), $id ? __('Edit Rule') : __('New Rule'));

        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getRuleId() ? $model->getRuleName() : __('New Rule')
        );
        $this->_view->renderLayout();
    }
}
