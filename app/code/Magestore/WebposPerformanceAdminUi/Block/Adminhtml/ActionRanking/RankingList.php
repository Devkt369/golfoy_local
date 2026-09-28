<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml\ActionRanking;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ObjectManager;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid\ActionRanking;
use Magestore\WebposPerformance\Model\ActionTypes;

/**
 * Class RankingList
 *
 * List block
 */
class RankingList extends Template
{
    protected $_template = 'Magestore_WebposPerformanceAdminUi::action/action_list.phtml';

    /* @var ActionRanking */
    protected $actionRanking;

    /* @var ActionTypes */
    protected $actionTypes;

    /**
     * Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $objectManager = ObjectManager::getInstance();
        $this->actionRanking = $objectManager->get(
            ActionRanking::class
        );
        $this->actionTypes = $objectManager->get(
            ActionTypes::class
        );
    }

    /**
     * Get action collection
     *
     * @return ActionRanking
     */
    public function getActionCollection()
    {
        return $this->actionRanking;
    }

    /**
     * Get action label
     *
     * @param string $actionCode
     * @return string
     */
    public function getActionLabel(string $actionCode)
    {
        return $this->actionTypes->getActionLabel($actionCode);
    }

    /**
     * Get reload detail url
     *
     * @return string
     */
    public function getAllActionResponseTable()
    {
        return $this->getUrl('webposperformance/index/overview');
    }
}
