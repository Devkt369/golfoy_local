<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
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
interface LocationSpecialDayLinkSearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get schedule list
     *
     * @return \Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterface[]
     */
    public function getItems();

    /**
     * Set schedule list
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkInterface[] $items
     * @return void
     */
    public function setItems(array $items);
}
