<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Model\Source\Adminhtml;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magestore\InventoryMovementApi\Model\ActionTypes;

/**
 * Source model
 */
class ActionType extends AbstractSource
{
    /**
     * @var ActionTypes
     */
    protected $actionTypes;

    /**
     * ActionType constructor.
     * @param ActionTypes $actionTypes
     */
    public function __construct(
        ActionTypes $actionTypes
    ) {
        $this->actionTypes = $actionTypes;
    }

    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        $items = $this->actionTypes->getData();
        $options = [];
        if (count($items)) {
            foreach ($items as $actionType => $label) {
                $options[] = ['value' => $actionType, 'label' => $label];
            }
        }
        return $options;
    }
}
