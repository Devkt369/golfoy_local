<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Slider;

use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use ArtM\ProductSlider\Model\SliderFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

class Duplicate extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::slider_duplicate';

    public function __construct(
        Context $context,
        private readonly SliderRepositoryInterface $sliderRepository,
        private readonly SliderFactory $sliderFactory
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $sliderId = (int)$this->getRequest()->getParam('slider_id');

        if (!$sliderId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a slider to duplicate.'));
            return $resultRedirect->setPath('*/*/index');
        }

        try {
            $original = $this->sliderRepository->getById($sliderId);
            $copy = $this->sliderFactory->create();
            $copyData = $original->getData();
            unset($copyData['slider_id'], $copyData['created_at'], $copyData['updated_at']);
            $copyData['title'] = (string)$copyData['title'] . ' (Copy)';
            $copyData['identifier'] = substr((string)$copyData['identifier'] . '-copy-' . time(), 0, 255);
            $copy->setData($copyData);
            $this->sliderRepository->save($copy);
            $this->messageManager->addSuccessMessage(__('The slider has been duplicated.'));
        } catch (LocalizedException $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        } catch (\Throwable $exception) {
            $this->messageManager->addErrorMessage(__('Unable to duplicate the slider.'));
        }

        return $resultRedirect->setPath('*/*/index');
    }
}
