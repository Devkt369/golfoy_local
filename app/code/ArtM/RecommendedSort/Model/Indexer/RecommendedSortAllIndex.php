<?php

namespace ArtM\RecommendedSort\Model\Indexer;

use DateTime;
use ArtM\RecommendedSort\Services\Indexer\RecommendedSortAllIndexer;
use Magento;

class RecommendedSortAllIndex implements Magento\Framework\Indexer\ActionInterface, Magento\Framework\Mview\ActionInterface
{
    private $recommendedSortIndexer;

    public function __construct(
        RecommendedSortAllIndexer $recommendedSortIndexer
    ) {
        $this->recommendedSortIndexer = $recommendedSortIndexer;
    }

    public function execute($ids)
    {
       $this->recommendedSortIndexer->reindex();
    }

    public function executeFull()
    {
        $this->recommendedSortIndexer->reindex();
    }

    public function executeList(array $ids)
    {
        return null;
    }

    public function executeRow($id)
    {
        $this->recommendedSortIndexer->reindex();
    }
}
