<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectMigrate\Api\Data;

/**
 * Interface map between pos's location and store pickup's store
 *
 * @api
 */
interface LocationStoreMapInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const STOREPICKUP_ID = 'storepickup_id';
    const SOURCE_CODE = 'source_code';
    const STOCK_ID = 'stock_id';
    const LOCATION_ID = 'location_id';
    /**#@-*/

    /**
     * Get Storepickup Id
     *
     * @return int|null
     */
    public function getStorepickupId(): ?int;

    /**
     * Set Storepickup Id
     *
     * @param int|null $storepickupId
     * @return $this
     */
    public function setStorepickupId(?int $storepickupId);

    /**
     * Get Source Code
     *
     * @return string|null
     */
    public function getSourceCode(): ?string;

    /**
     * Set Source Code
     *
     * @param string|null $sourceCode
     * @return $this
     */
    public function setSourceCode(?string $sourceCode);

    /**
     * Get Stock Id
     *
     * @return int|null
     */
    public function getStockId(): ?int;

    /**
     * Set Stock Id
     *
     * @param int|null $stockId
     * @return $this
     */
    public function setStockId(?int $stockId);

    /**
     * Get Location Id
     *
     * @return int|null
     */
    public function getLocationId(): ?int;

    /**
     * Set Location Id
     *
     * @param int|null $locationId
     * @return $this
     */
    public function setLocationId(?int $locationId);
}
