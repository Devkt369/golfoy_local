<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Ui\Component\Form\Fieldset;

use Magento\Ui\Component\Form\Fieldset;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

/**
 * Class BranchRequest DeliveryHistory
 */
class DeliveryHistory extends Fieldset
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
            $this->_data['config']['componentDisabled'] = !$transfer->showDeliveryTab();
        }
    }
}
