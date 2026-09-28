<?php
/**
 * Copyright © 2021 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Model;

use Magestore\Core\Api\GetSourcesCodeByStockIdInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;

/**
 * Class GetSourcesCodeByStockId
 *
 * Get source code by stock id service
 */
class GetSourcesCodeByStockId implements GetSourcesCodeByStockIdInterface
{
    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $getSourcesAssignedToStock;

    /**
     * GetSourcesCodeByStockId constructor.
     *
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStock
     */
    public function __construct(
        GetSourcesAssignedToStockOrderedByPriorityInterface $getSourcesAssignedToStock
    ) {
        $this->getSourcesAssignedToStock = $getSourcesAssignedToStock;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $stockId)
    {
        $sources = $this->getSourcesAssignedToStock->execute($stockId);
        $sourcesCode = [];
        if ($sources) {
            foreach ($sources as $source) {
                if ($source->isEnabled()) {
                    $sourcesCode[] = $source->getSourceCode();
                }
            }
        }
        return $sourcesCode;
    }
}
