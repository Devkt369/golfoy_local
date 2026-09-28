<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Customercredit\Model;

use Magento\Framework\Api\SearchResults;
use Magestore\Customercredit\Api\Data\TransactionSearchResultsInterface;

/**
 * Class TransactionSearchResults
 *
 * Implement Transaction Search Results
 */
class TransactionSearchResults extends SearchResults implements TransactionSearchResultsInterface
{
}
