<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Model;

use Magento\Framework\Api\SearchResults;
use Magestore\ClickAndCollectMigrate\Api\Data\LocationStoreMapSearchResultsInterface;

/**
 * Class LocationStoreMapSearchResults
 *
 * Implement Location Store Map Search Results
 */
class LocationStoreMapSearchResults extends SearchResults implements LocationStoreMapSearchResultsInterface
{
}
