<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml;

use Magento\Framework\App\ObjectManager;
use Magestore\Webpos\Model\Source\Adminhtml\Pos as PosSource;

/**
 * Class PosRanking
 *
 * Used fpr Pos ranking
 */
class PosRanking extends AbstractBlock
{
    /**
     * Get reload list url
     *
     * @return string
     */
    public function getReloadListUrl()
    {
        return $this->getUrl('webposperformance/byPos/reloadRankingList');
    }

    /**
     * Get reload detail url
     *
     * @return string
     */
    public function getReloadDetailUrl()
    {
        return $this->getUrl('webposperformance/byPos/reloadDetailAction');
    }

    /**
     * Load more url
     *
     * @return string
     */
    public function getLoadMoreUrl()
    {
        return $this->getUrl('webposperformance/byPos/loadMore');
    }

    /**
     * Get POS name
     *
     * @param int $posId
     * @return string
     */
    public function getPosName(int $posId)
    {
        $objectManager = ObjectManager::getInstance();
        /* @var PosSource $posSource */
        $posSource = $objectManager->get(
            PosSource::class
        );
        $option = $posSource->getOptionArray();
        if (isset($option[$posId])) {
            return $option[$posId];
        } else {
            return '';
        }
    }
}
