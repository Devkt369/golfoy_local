<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Model\InventoryTransfer\Option;

use Magestore\BranchRequest\Model\InventoryTransfer;

/**
 * Class BranchRequest Status
 */
class Status implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Retrieve option array
     *
     * @return string[]
     */
    public function getOptionHash()
    {
        return [
            InventoryTransfer::STATUS_NEW => __('New'),
            InventoryTransfer::STATUS_WAIT_APPROVE => __('Wait to Approve'),
            InventoryTransfer::STATUS_APPROVED => __('Approved'),
            InventoryTransfer::STATUS_PROCESSING => __('Processing'),
            InventoryTransfer::STATUS_CANCELED => __('Canceled'),
            InventoryTransfer::STATUS_CLOSED => __('Closed'),
        ];
    }

    /**
     * Get model option hash as array
     *
     * @return array
     */
    public function getOptionArray()
    {
        $options = [];
        foreach ($this->getOptionHash() as $value => $label) {
            $options[] = [
                'value'    => $value,
                'label'    => $label
            ];
        }
        return $options;
    }

    /**
     * Return array of options as value-label pairs.
     *
     * @return array Format: array(array('value' => '<value>', 'label' => '<label>'), ...)
     */
    public function toOptionArray()
    {
        return $this->getOptionArray();
    }
}
