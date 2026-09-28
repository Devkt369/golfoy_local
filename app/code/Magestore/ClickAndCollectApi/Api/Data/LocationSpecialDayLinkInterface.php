<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Data;

/**
 * Interface link between location and special day
 *
 * @api
 */
interface LocationSpecialDayLinkInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const LINK_ID = 'link_id';
    const LOCATION_ID = 'location_id';
    const SPECIALDAY_ID = 'specialday_id';
    /**#@-*/

    /**
     * Get Link Id
     *
     * @return int|null
     */
    public function getLinkId(): ?int;

    /**
     * Set Link Id
     *
     * @param int|null $linkId
     * @return $this
     */
    public function setLinkId(?int $linkId);

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

    /**
     * Get Special Day Id
     *
     * @return int|null
     */
    public function getSpecialDayId(): ?int;

    /**
     * Set Special Day Id
     *
     * @param int|null $specialDayId
     * @return $this
     */
    public function setSpecialDayId(?int $specialDayId);

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkExtensionInterface|null
     */
    public function getExtensionAttributes()
    : ?\Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\ClickAndCollectApi\Api\Data\LocationSpecialDayLinkExtensionInterface $extensionAttributes
    ): void;
}
