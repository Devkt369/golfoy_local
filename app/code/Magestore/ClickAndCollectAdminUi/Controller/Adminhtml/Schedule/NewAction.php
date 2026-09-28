<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectAdminUi\Controller\Adminhtml\Schedule;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;

/**
 * Action new schedule
 */
class NewAction extends Action implements HttpGetActionInterface
{
    /**
     * Execute method.
     *
     * @return void
     */
    public function execute()
    {
        $this->_forward('edit');
    }
}
