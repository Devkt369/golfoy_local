<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\BranchRequest\Controller\Adminhtml\InventoryTransfer;

/**
 * Class BranchRequest InventoryTransfer
 *
 * @SuppressWarnings(PHPMD)
 */
abstract class InventoryTransfer extends \Magestore\BranchRequest\Controller\Adminhtml\AbstractAction
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_BranchRequest::manage';
}
