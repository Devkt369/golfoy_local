<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Controller\Adminhtml\SpecialDay;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * New special day action
 */
class NewAction extends Action implements HttpGetActionInterface
{
    /**
     * Execute method.
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
