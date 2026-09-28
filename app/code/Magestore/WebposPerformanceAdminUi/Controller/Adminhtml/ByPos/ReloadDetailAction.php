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
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\View\LayoutFactory;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid\PosRanking;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid\PosRankingFactory;
use Magestore\WebposPerformanceAdminUi\Block\Adminhtml\PosRanking\PosDetail;
use Magestore\WebposPerformance\Model\ActionTypes;

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
     * @var PosRankingFactory
     */
    protected $posRankingFactory;

    /**
     * @var ActionTypes
     */
    protected $actionTypes;

    /**
     * ReloadDetailAction constructor.
     *
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param LayoutFactory $layoutFactory
     * @param PosRankingFactory $posRankingFactory
     * @param ActionTypes $actionTypes
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        LayoutFactory $layoutFactory,
        PosRankingFactory $posRankingFactory,
        ActionTypes $actionTypes
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->layoutFactory = $layoutFactory;
        $this->posRankingFactory = $posRankingFactory;
        $this->actionTypes = $actionTypes;
    }

    /**
     * Reload ranking list
     *
     * @return Json
     */
    public function execute()
    {
        $output = $this->layoutFactory->create()
            ->createBlock(PosDetail::class)
            ->toHtml();
        /* @var PosRanking $posCollection */
        $posCollection = $this->posRankingFactory->create();
        $posCollection->setGroupBy(['action_code']);
        $labels = [];
        $data = [];
        foreach ($posCollection as $pos) {
            $labels[] = $this->actionTypes->getActionLabel($pos->getActionCode());
            $data[] = $pos->getAveragePeriod();
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
