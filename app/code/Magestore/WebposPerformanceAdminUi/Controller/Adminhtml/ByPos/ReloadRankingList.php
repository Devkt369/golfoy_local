<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceAdminUi\Controller\Adminhtml\ByPos;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\View\LayoutFactory;
use Magestore\WebposPerformanceAdminUi\Block\Adminhtml\PosRanking\PosList;

/**
 * Class ReloadRankingList
 *
 * Use for performance admin ui
 */
class ReloadRankingList extends Action implements HttpPostActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_WebposPerformanceAdminUi::reports';

    /**
     * @var RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @param Context $context
     * @param RawFactory $resultRawFactory
     * @param LayoutFactory $layoutFactory
     */
    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        LayoutFactory $layoutFactory
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->layoutFactory = $layoutFactory;
    }

    /**
     * Reload ranking list
     *
     * @return Raw
     */
    public function execute()
    {
        $output = $this->layoutFactory->create()
            ->createBlock(PosList::class)
            ->toHtml();
        $resultRaw = $this->resultRawFactory->create();
        return $resultRaw->setContents($output);
    }
}
