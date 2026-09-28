<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Slider;

use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class Delete extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::slider_delete';

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $sliderId = (int)$this->getRequest()->getParam('slider_id');

        if (!$sliderId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a slider to delete.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $this->sliderRepository->deleteById($sliderId);
            $this->messageManager->addSuccessMessage(__('The slider has been deleted.'));
            return $resultRedirect->setPath('*/*/index');
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->messageManager->addErrorMessage(__('Unable to delete the slider.'));
        }

        return $resultRedirect->setPath('*/*/edit', ['slider_id' => $sliderId]);
    }
}
