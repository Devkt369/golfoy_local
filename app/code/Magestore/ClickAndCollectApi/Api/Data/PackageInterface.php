<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\ClickAndCollectApi\Api\Data;

/**
 * Represents Package
 *
 * Used fully qualified namespaces in annotations for proper work of WebApi request parser
 *
 * @api
 */
interface PackageInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const PACKAGE_ID = 'package_id';
    const ORDER_ID = 'order_id';
    const LOCATION_ID = 'location_id';
    const STATUS = 'status';
    /**#@-*/

    /**
     * Constants for status
     */
    const STATUS_READY_TO_PICK = 'ready_to_pick';
    const STATUS_CANCELED = 'canceled';
    const STATUS_DELIVERED = 'delivered';
    /**#@-*/

    /**
     * Get Package Id
     *
     * @return int|null
     */
    public function getPackageId(): ?int;

    /**
     * Set Package Id
     *
     * @param int|null $packageId
     * @return $this
     */
    public function setPackageId(?int $packageId);

    /**
     * Get Order Id
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Set Order Id
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId);

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
     * Get Status
     *
     * @return string|null
     */
    public function getStatus(): ?string;

    /**
     * Set Status
     *
     * @param string|null $status
     * @return $this
     */
    public function setStatus(?string $status);

    /**
     * Retrieve existing extension attributes object
     *
     * @return \Magestore\ClickAndCollectApi\Api\Data\PackageExtensionInterface|null
     */
    public function getExtensionAttributes()
    : ?\Magestore\ClickAndCollectApi\Api\Data\PackageExtensionInterface;

    /**
     * Set an extension attributes object
     *
     * @param \Magestore\ClickAndCollectApi\Api\Data\PackageExtensionInterface $extensionAttributes
     * @return void
     */
    public function setExtensionAttributes(
        \Magestore\ClickAndCollectApi\Api\Data\PackageExtensionInterface $extensionAttributes
    ): void;
}
