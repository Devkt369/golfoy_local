<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Group;

use ArtM\ProductSlider\Api\SliderGroupRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::group_delete';

    public function __construct(
        Context $context,
        private readonly SliderGroupRepositoryInterface $sliderGroupRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $groupId = (int)$this->getRequest()->getParam('group_id');

        if (!$groupId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a slider group to delete.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->sliderGroupRepository->deleteById($groupId);
            $this->messageManager->addSuccessMessage(__('The slider group has been deleted.'));
            return $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->messageManager->addErrorMessage(__('Unable to delete the slider group.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['group_id' => $groupId]);
    }
}
