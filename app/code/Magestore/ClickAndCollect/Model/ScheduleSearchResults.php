<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Api\SearchResults;
use Magestore\ClickAndCollectApi\Api\Data\ScheduleSearchResultsInterface;

/**
 * Class ScheduleSearchResults
 *
 * Implement Schedule Search Results
 */
class ScheduleSearchResults extends SearchResults implements ScheduleSearchResultsInterface
{
}
