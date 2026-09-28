<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceAdminUi\Controller\Adminhtml\ByAction;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Magestore\Webpos\Model\Source\Adminhtml\Pos as PosSource;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid\ActionRankingFactory;
use Magestore\WebposPerformanceAdminUi\Block\Adminhtml\ActionRanking\RankingDetail;
use Magestore\WebposPerformanceAdminUi\Helper\Config as ConfigHelper;

/**
 * Class ReloadDetailAction
 *
 * Use for reload detail
 */
class ReloadDetailAction extends Action implements HttpPostActionInterface
{
    /**
     * Authorization
     */
    const ADMIN_RESOURCE = 'Magestore_WebposPerformanceAdminUi::reports';
    
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var LayoutFactory
     */
    protected $layoutFactory;

    /**
     * @var ActionRankingFactory
     */
    protected $actionRankingFactory;

    /**
     * @var PosSource
     */
    protected $posSource;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * ReloadDetailAction constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param LayoutFactory $layoutFactory
     * @param ActionRankingFactory $actionRankingFactory
     * @param PosSource $posSource
     * @param ConfigHelper $configHelper
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory,
        ActionRankingFactory $actionRankingFactory,
        PosSource $posSource,
        ConfigHelper $configHelper
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->actionRankingFactory = $actionRankingFactory;
        $this->posSource = $posSource;
        $this->configHelper = $configHelper;
    }

    /**
     * Reload ranking list
     *
     * @return Json
     */
    public function execute()
    {
        $output = $this->layoutFactory->create()
            ->createBlock(RankingDetail::class)
            ->toHtml();
        $posSource = $this->posSource->getOptionArray();
        $actionCollection = $this->actionRankingFactory->create();
        $actionCollection->setGroupBy(['pos_id']);
        $actionCollection->setPageSize($this->configHelper->getNumberOfPosInChart());
        $actionCollection->setCurPage(1);
        $labels = [];
        $data = [];
        foreach ($actionCollection as $action) {
            $labels[] = $posSource[$action->getPosId()];
            $data[] = $action->getAveragePeriod();
        }
        $json = $this->jsonFactory->create();
        $json->setData(
            [
                'detail_block' => $output,
                'data_set_graph' => [
                    'labels' => $labels,
                    'data' => $data
                ]
            ]
        );
        return $json;
    }
}
