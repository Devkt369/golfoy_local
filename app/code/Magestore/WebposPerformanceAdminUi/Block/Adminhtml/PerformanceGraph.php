<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types = 1);

namespace Magestore\WebposPerformanceAdminUi\Block\Adminhtml;

use Magento\Framework\App\ObjectManager;
use Magestore\WebposPerformance\Model\ResourceModel\PerformanceTrack\Collection;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Class PerformanceGraph
 *
 * Used for display the graph
 */
class PerformanceGraph extends AbstractBlock
{
    /**
     * Get data for graph
     *
     * @return string
     */
    public function getDataForGraph()
    {
        $objectManager = ObjectManager::getInstance();
        $date = $this->getRequest()->getParam('date');
        $posId = $this->getRequest()->getParam('pos_id');
        /* @var Collection $collection */
        $collection = $objectManager->create(Collection::class)
            ->filterTrackLog($date, $posId);
        /* @var Json $jsonEncode */
        $jsonEncode = $objectManager->create(Json::class);
        return $jsonEncode->serialize($collection);
    }
}
