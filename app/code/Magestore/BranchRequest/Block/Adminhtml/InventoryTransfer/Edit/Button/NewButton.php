<?php

/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\Edit\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magestore\BranchRequest\Block\Adminhtml\InventoryTransfer\AbstractInventoryTransfer;

/**
 * Class BranchRequest NewButton
 */
class NewButton extends AbstractInventoryTransfer implements ButtonProviderInterface
{
    /**
     * Get Button Data
     *
     * @return array
     */
    public function getButtonData()
    {
        if (!$this->authorization->isAllowed('Magestore_BranchRequest::addnew')) {
            return [];
        }

        $url = $this->getUrl(
            '*/*/new',
            ['_secure' => true]
        );
        return [
            'label' => __('Add Branch Request'),
            'on_click' => sprintf("location.href = '%s';", $url),
            'class' => 'add primary',
            'sort_order' => 20
        ];
    }
}
