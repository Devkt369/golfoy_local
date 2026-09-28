<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml\PosRanking;

use Magento\Backend\Block\Template;
use Magento\Framework\App\ObjectManager;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Grid\PosRanking;

/**
 * Class RankingList
 *
 * List block
 */
class PosList extends Template
{
    const PAGE_SIZE = 12;

    protected $_template = 'Magestore_WebposPerformanceAdminUi::pos/pos_list.phtml';

    /* @var PosRanking */
    protected $posRanking;

    /**
     * Constructor
     */
    public function _construct()
    {
        parent::_construct();
        $objectManager = ObjectManager::getInstance();
        $this->posRanking = $objectManager->create(
            PosRanking::class
        );
    }

    /**
     * Get action collection
     *
     * @return PosRanking
     */
    public function getPosCollection()
    {
        if (!$this->getRequest()->getParam('pageNumber')) {
            return $this->posRanking->setPageSize(self::PAGE_SIZE)->setCurPage(1);
        } else {
            return $this->posRanking->setPageSize(self::PAGE_SIZE)->setCurPage(
                $this->getRequest()->getParam('pageNumber')
            );
        }
    }

    /**
     * Get number page
     *
     * @return int
     */
    public function getNumberPage()
    {
        return ($this->posRanking->getSize() / self::PAGE_SIZE) + 1;
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
