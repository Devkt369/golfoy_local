<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Edit\Steps;

/**
 * Class BranchRequest StatusWait
 */
class StatusWait extends \Magento\Ui\Block\Component\StepsWizard\StepAbstract
{
    /**
     * Get Caption
     *
     * @return \Magento\Framework\Phrase|string
     */
    public function getCaption()
    {
        return __('Wait to Approve');
    }
}
