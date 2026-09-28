<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollect\Model;

use Magento\Framework\Api\SearchResults;
use Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkSearchResultsInterface;

/**
 * @inheritDoc
 */
class LocationSpecialDayLinkSearchResults extends SearchResults implements LocationSpecialDayLinkSearchResultsInterface
{
}
