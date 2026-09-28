<?php

declare(strict_types=1);

namespace ArtM\CustomerSurvey\Model\Source;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroups implements OptionSourceInterface
{
    /**
     * @var CollectionFactory
     */
    private $groupCollectionFactory;

    public function __construct(CollectionFactory $groupCollectionFactory)
    {
        $this->groupCollectionFactory = $groupCollectionFactory;
    }

    public function toOptionArray(): array
    {
        $options = [];
        $collection = $this->groupCollectionFactory->create();

        foreach ($collection as $group) {
            $options[] = [
                'value' => (string)$group->getId(),
                'label' => (string)$group->getCode(),
            ];
        }

        return $options;
    }
}