<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Controller\Adminhtml\Group;

use ArtM\ProductSlider\Api\SliderGroupRepositoryInterface;
use ArtM\ProductSlider\Model\ResourceModel\SliderGroup\CollectionFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Ui\Component\MassAction\Filter;

class MassEnable extends Action
{
    public const ADMIN_RESOURCE = 'ArtM_ProductSlider::group_mass';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly SliderGroupRepositoryInterface $sliderGroupRepository
    ) {
        parent::__construct($context);
    }

    public function execute(): Redirect
    {
        $collection = $this->collectionFactory->create();
        $selected = (array)$this->getRequest()->getParam('selected', []);
        if ($selected !== []) {
            $collection->addFieldToFilter('group_id', ['in' => array_map('intval', $selected)]);
        } else {
            $collection = $this->filter->getCollection($collection);
        }
        $updated = 0;

        foreach ($collection as $group) {
            $group->setData('status', 1);
            $this->sliderGroupRepository->save($group);
            $updated++;
        }

        if ($updated) {
            $this->messageManager->addSuccessMessage(__('%1 slider group(s) were enabled.', $updated));
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
