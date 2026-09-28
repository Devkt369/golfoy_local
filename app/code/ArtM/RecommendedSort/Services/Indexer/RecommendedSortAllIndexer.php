<?php

namespace ArtM\RecommendedSort\Services\Indexer;

use ArtM\RecommendedSort\Helper\Data;

class RecommendedSortAllIndexer
{
    protected $helper;

    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    public function reindex(){
        $this->helper->RunBatchAll();
    }

}
