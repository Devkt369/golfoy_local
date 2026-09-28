<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Stocktaking\Api;

use Magestore\Stocktaking\Model\ResourceModel\StocktakingArchive\StocktakingArchiveItem\Collection
    as StocktakingArchiveItemCollection;

/**
 * Interface StocktakingArchiveItemRepositoryInterface
 *
 * Used for stock taking archive item repository
 */
interface StocktakingArchiveItemRepositoryInterface
{
    /**
     * Get Stocktaking archive item collection by Stocktaking id
     *
     * @param int $stocktakingId
     * @return StocktakingArchiveItemCollection
     */
    public function getListByStocktakingId(int $stocktakingId);
}
