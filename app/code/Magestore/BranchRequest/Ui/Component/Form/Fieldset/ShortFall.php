<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\Component\Form\Fieldset;

use Magento\Ui\Component\Form\Fieldset;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;

/**
 * Class BranchRequest ShortFall Fieldset
 */
class ShortFall extends Fieldset
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * ReceiveHistory constructor.
     *
     * @param ContextInterface $context
     * @param \Magento\Framework\Registry $registry
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface            $context,
        \Magento\Framework\Registry $registry,
        array                       $components = [],
        array                       $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->registry = $registry;
        $this->context = $context;
    }

    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        /** @var \Magestore\BranchRequest\Model\InventoryTransfer $transfer */
        if ($transfer = $this->registry->registry('current_inventory_transfer')) {
            if ($transfer->getStatus() == InventoryTransferInterface::STATUS_PROCESSING
                || $transfer->getStatus() == InventoryTransferInterface::STATUS_CLOSED) {
                $this->_data['config']['componentDisabled'] = false;
            } else {
                $this->_data['config']['componentDisabled'] = true;
            }
        }
    }
}
