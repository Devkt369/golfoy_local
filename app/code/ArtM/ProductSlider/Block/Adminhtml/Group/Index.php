<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Block\Adminhtml\Group;

use ArtM\ProductSlider\Model\ResourceModel\SliderGroup\CollectionFactory;
use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\FormKey;

class Index extends Template
{
    public function __construct(
        Context $context,
        private readonly CollectionFactory $collectionFactory,
        private readonly FormKey $formKeyService,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getItems(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->setOrder('group_id', 'DESC');
        return $collection->getItems();
    }

    public function getFormKey(): string
    {
        return $this->formKeyService->getFormKey();
    }

    public function getAddUrl(): string
    {
        return $this->getUrl('artm_productslider/group/new');
    }

    public function getEditUrl(int $groupId): string
    {
        return $this->getUrl('artm_productslider/group/edit', ['group_id' => $groupId]);
    }

    public function getDeleteUrl(int $groupId): string
    {
        return $this->getUrl('artm_productslider/group/delete', ['group_id' => $groupId]);
    }

    public function getMassEnableUrl(): string
    {
        return $this->getUrl('artm_productslider/group/massenable');
    }

    public function getMassDisableUrl(): string
    {
        return $this->getUrl('artm_productslider/group/massdisable');
    }

    public function getMassDeleteUrl(): string
    {
        return $this->getUrl('artm_productslider/group/massdelete');
    }

    public function getStatusLabel(int $status): string
    {
        return $status === 1 ? (string)__('Enabled') : (string)__('Disabled');
    }
}
