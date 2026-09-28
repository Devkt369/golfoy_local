<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Model\Source\Adminhtml;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magestore\InventoryMovementApi\Model\OrderTypes;

/**
 * Source model
 */
class OrderType extends AbstractSource
{
    /**
     * @var OrderTypes
     */
    protected $orderTypes;

    /**
     * OrderType constructor.
     * @param OrderTypes $orderTypes
     */
    public function __construct(
        OrderTypes $orderTypes
    ) {
        $this->orderTypes = $orderTypes;
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        $items = $this->orderTypes->getData();
        $options = [];
        if (count($items)) {
            foreach ($items as $type => $item) {
                $label = isset($item['label']) ? $item['label'] : '';
                $options[] = ['value' => $type, 'label' => __($label)];
            }
        }
        return $options;
    }
}
