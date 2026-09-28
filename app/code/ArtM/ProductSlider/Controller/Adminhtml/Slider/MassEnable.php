<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Slider;

use ArtM\ProductSlider\Api\SliderRepositoryInterface;
use ArtM\ProductSlider\Model\ResourceModel\Slider\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

class MassEnable extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::slider_mass';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderRepositoryInterface $sliderRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $collection = $this->collectionFactory->create();
        $selected = (array)$this->getRequest()->getParam('selected', []);
        if ($selected !== []) {
            $collection->addFieldToFilter('slider_id', ['in' => array_map('intval', $selected)]);
        } else {
            $collection = $this->filter->getCollection($collection);
        }
        $updated = 0;

        foreach ($collection as $slider) {
            $slider->setData('status', 1);
            $this->sliderRepository->save($slider);
            $updated++;
        }

        if ($updated) {
            $this->messageManager->addSuccessMessage(__('%1 slider(s) were enabled.', $updated));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
