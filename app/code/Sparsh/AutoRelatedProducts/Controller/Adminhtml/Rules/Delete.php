<?php
/**
 * Class Delete
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
 * Class Delete
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Delete extends \Sparsh\AutoRelatedProducts\Controller\Adminhtml\Rules\Rule
{
    /**
     * Delete action
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface|void
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            if (!is_array($id)) {
                $id = [$id];
            }
            try {
                foreach ($id as $id) {
                    $model = $this->ruleFactory->create();
                    $model->load($id);
                    $model->delete();
                }
                $this->messageManager->addSuccessMessage(__('The rule has been deleted successfully.'));
                $this->_redirect('sparsh_auto_related_products/rules/');
                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->messageManager->addErrorMessage(
                    __('We can\'t delete the rule right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
                $this->_redirect('sparsh_auto_related_products/rules/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        } else {
            $this->messageManager->addErrorMessage(__('We can\'t find a rule to delete.'));
            $this->_redirect('sparsh_auto_related_products/rules/');
        }
    }
}
