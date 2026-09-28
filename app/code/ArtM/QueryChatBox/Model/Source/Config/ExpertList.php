<?php

declare(strict_types=1);

namespace ArtM\QueryChatBox\Model\Source\Config;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use ArtM\Expertlist\Model\ResourceModel\ExpertModel\CollectionFactory;

class ExpertList extends AbstractSource
{
    protected $expertModelFactory;
    public function __construct(CollectionFactory $expertModelFactory)
    {
        $this->expertModelFactory = $expertModelFactory;
    }
    /**
     * @return array
     */
    public function getAllOptions(): array
    {
        $collection = $this->expertModelFactory->create();
        $statuses = $collection->toOptionArray();
        array_unshift($statuses, ['value' => '', 'label' => __('-- Assign Expert --')]);
        return $statuses;
    }
}