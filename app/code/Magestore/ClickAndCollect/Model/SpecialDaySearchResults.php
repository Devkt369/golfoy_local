<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Api\SearchResults;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDaySearchResultsInterface;

/**
 * Class SpecialDaySearchResults
 *
 * Implement Special Day Search Results
 */
class SpecialDaySearchResults extends SearchResults implements SpecialDaySearchResultsInterface
{
}
