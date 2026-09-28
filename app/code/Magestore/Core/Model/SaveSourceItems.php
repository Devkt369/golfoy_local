<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Core\Model;

use Magento\InventoryApi\Api\SourceItemsSaveInterface;
use Magento\InventoryImportExport\Model\Import\SourceItemConvert;
use Magestore\Core\Api\SaveSourceItemsInterface;

/**
 * Save multi source items from array data
 */
class SaveSourceItems implements SaveSourceItemsInterface
{
    /**
     * @var SourceItemConvert
     */
    private $sourceItemConvert;

    /**
     * @var SourceItemsSaveInterface
     */
    private $sourceItemsSave;

    /**
     * SaveSourceItems constructor.
     *
     * @param SourceItemConvert $sourceItemConvert
     * @param SourceItemsSaveInterface $sourceItemsSave
     */
    public function __construct(
        SourceItemConvert $sourceItemConvert,
        SourceItemsSaveInterface $sourceItemsSave
    ) {
        $this->sourceItemConvert = $sourceItemConvert;
        $this->sourceItemsSave = $sourceItemsSave;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $sourceItems)
    {
        if (!empty($sourceItems)) {
            $items = $this->sourceItemConvert->convert($sourceItems);
            $this->sourceItemsSave->execute($items);
        }
    }
}
