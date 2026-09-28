<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\WebposPerformanceAdminUi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Config
 *
 * Use for get config
 */
class Config extends AbstractHelper
{
    const NUMBER_OF_POS_IN_CHART_XML_PATH = 'webposperformance/by_action/pos_number_in_chart';

    /**
     * Get number of pos in chart
     *
     * @return string
     */
    public function getNumberOfPosInChart()
    {
        return $this->scopeConfig->getValue(
            self::NUMBER_OF_POS_IN_CHART_XML_PATH,
            ScopeInterface::SCOPE_STORE
        );
    }
}
