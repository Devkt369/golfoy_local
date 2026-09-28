<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Data;

/**
 * Search results of Repository::getList method
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface PackageItemSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get package item list
     *
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface[]
     */
    public function getItems();

    /**
     * Set package item list
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageItemInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
