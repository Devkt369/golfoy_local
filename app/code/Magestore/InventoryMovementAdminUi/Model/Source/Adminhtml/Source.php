<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Model\Source\Adminhtml;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\SourceRepositoryInterface;

/**
 * Source model
 */
class Source extends AbstractSource
{
    /**
     * @var SourceRepositoryInterface
     */
    protected $sourceRepository;

    /**
     * Source constructor.
     * @param SourceRepositoryInterface $sourceRepository
     */
    public function __construct(
        SourceRepositoryInterface $sourceRepository
    ) {
        $this->sourceRepository = $sourceRepository;
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        $collection = $this->sourceRepository->getList();
        $options = [];
        $items = $collection->getItems();
        $options[] = ['value' => '', 'label' => __('-- Please Select --')];
        if (count($items)) {
            /** @var SourceInterface $source */
            foreach ($items as $source) {
                $label = $source->getName() . " (" . $source->getSourceCode() . ")";
                $options[] = ['value' => $source->getSourceCode(), 'label' => $label];
            }
        }
        return $options;
    }
}
