<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceAdminUi\Controller\Adminhtml\ByPos;

use Magestore\WebposPerformanceAdminUi\Controller\Adminhtml\Ranking;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Class Index
 *
 * Use for performance admin ui
 */
class Index extends Ranking implements HttpGetActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_WebposPerformanceAdminUi::report_by_pos';

    /**
     * Active menu
     */
    const MENU_ITEM = 'Magestore_WebposPerformanceAdminUi::report_by_pos';

    /**
     * Stocktaking list action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        $this->_showLastExecutionTime();
        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu(self::MENU_ITEM);
        $resultPage->getConfig()->getTitle()->prepend(__('POS Ranking Performance'));
        return $resultPage;
    }
}
