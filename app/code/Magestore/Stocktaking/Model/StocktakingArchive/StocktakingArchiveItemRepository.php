<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Stocktaking\Model\StocktakingArchive;

use Magestore\Stocktaking\Api\Data\StocktakingArchiveItemInterface;
use Magestore\Stocktaking\Api\StocktakingArchiveItemRepositoryInterface;
use Magestore\Stocktaking\Model\ResourceModel\StocktakingArchive\StocktakingArchiveItem\Collection
    as StocktakingArchiveItemCollection;
use Magestore\Stocktaking\Model\ResourceModel\StocktakingArchive\StocktakingArchiveItem\CollectionFactory
    as StocktakingArchiveItemCollectionFactory;

/**
 * Class StocktakingArchiveItemRepository
 *
 * Used for stocktaking archive item repository
 */
class StocktakingArchiveItemRepository implements StocktakingArchiveItemRepositoryInterface
{

    /**
     * @var StocktakingArchiveItemCollectionFactory
     */
    protected $itemCollectionFactory;

    /**
     * StocktakingArchiveItemRepository constructor.
     *
     * @param StocktakingArchiveItemCollectionFactory $itemCollectionFactory
     */
    public function __construct(
        StocktakingArchiveItemCollectionFactory $itemCollectionFactory
    ) {
        $this->itemCollectionFactory = $itemCollectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function getListByStocktakingId(int $stocktakingId)
    {
        /** @var StocktakingArchiveItemCollection $itemCollection */
        $itemCollection = $this->itemCollectionFactory->create();
        $itemCollection->addFieldToFilter(
            StocktakingArchiveItemInterface::STOCKTAKING_ID,
            ['eq' => $stocktakingId]
        );
        return $itemCollection;
    }
}
