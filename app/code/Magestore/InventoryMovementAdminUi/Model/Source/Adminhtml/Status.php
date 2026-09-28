<?php

/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Model\Source\Adminhtml;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use Magestore\InventoryMovementApi\Api\Data\InventoryMovementInterface;

/**
 * Status model
 */
class Status extends AbstractSource
{
    /**
     * @inheritDoc
     */
    public function getAllOptions()
    {
        return [
            [
                'value' => InventoryMovementInterface::STATUS_SUCCESS,
                'label' => __('Completed')
            ],
            [
                'value' => InventoryMovementInterface::STATUS_FAIL,
                'label' => __('Failed')
            ]
        ];
    }
}
