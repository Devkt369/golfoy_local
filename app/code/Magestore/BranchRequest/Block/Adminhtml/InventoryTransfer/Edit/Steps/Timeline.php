<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Edit\Steps;

use Magestore\BranchRequest\Api\Data\InventoryTransfer\InventoryTransferInterface;

/**
 * Class BranchRequest Timeline
 */
class Timeline extends \Magento\Ui\Block\Component\StepsWizard
{
    /**
     * Wizard step template
     *
     * @var string
     */
    protected $_template = 'Magestore_BranchRequest::form/timeline.phtml';

    /**
     * @var null|\Magento\Ui\Block\Component\StepsWizard\StepInterface[]
     */
    private $steps;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * Timeline constructor
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry                      $registry,
        array                                            $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
    }

    /**
     * Get Current Step
     *
     * @return string
     */
    public function getCurrentStep()
    {
        $status = $this->getCurrentStatus();
        switch ($status) {
            case InventoryTransferInterface::STATUS_CLOSED:
                $step = 'variation-steps-wizard_completed';
                break;
            case InventoryTransferInterface::STATUS_CANCELED:
                $step = 'variation-steps-wizard_canceled';
                break;
            case InventoryTransferInterface::STATUS_PROCESSING:
                $step = 'variation-steps-wizard_processing';
                break;
            case InventoryTransferInterface::STATUS_APPROVED:
                $step = 'variation-steps-wizard_approved';
                break;
            case InventoryTransferInterface::STATUS_WAIT_APPROVE:
                $step = 'variation-steps-wizard_wait';
                break;
            case InventoryTransferInterface::STATUS_CANCELED:
                $step = 'variation-steps-wizard_cancel';
                break;
            default:
                $step = 'variation-steps-wizard_new';
                break;
        }
        return $step;
    }

    /**
     * Get Steps
     *
     * @return \Magento\Ui\Block\Component\StepsWizard\StepInterface[]|null
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSteps()
    {
        if ($this->steps === null) {
            foreach ($this->getLayout()->getChildBlocks($this->getNameInLayout()) as $step) {
                if ($step instanceof \Magento\Ui\Block\Component\StepsWizard\StepInterface) {
                    $this->steps[$step->getComponentName()] = $step;
                }
            }
        }
        return $this->steps;
    }

    /**
     * Get Current Status
     *
     * @return string
     */
    public function getCurrentStatus()
    {
        $status = '';
        $inventoryTransfer = $this->registry->registry('current_inventory_transfer');
        if (isset($inventoryTransfer) && is_object($inventoryTransfer) && $inventoryTransfer->getId()) {
            $status = $inventoryTransfer->getStatus();
        }
        return $status;
    }
}
