<?php

namespace ArtM\MsiPriority\Model;

use Magento\InventorySourceDeductionApi\Model\SourceDeductionRequest as MagentoSourceDeductionRequest;

class SourceDeductionRequest extends MagentoSourceDeductionRequest
{
    /**
     * Optional custom flag to force shipment
     *
     * @var bool|null
     */
    private ?bool $vinForceShipment = null;

    /**
     * Optional order ID
     *
     * @var string|null
     */
    private ?string $orderId = null;

    /**
     * Set the custom shipment flag
     *
     * @param bool $value
     * @return void
     */
    public function setVinForceShipment(bool $value): void
    {
        $this->vinForceShipment = $value;
    }

    /**
     * Get the custom shipment flag
     *
     * @return bool
     */
    public function getVinForceShipment(): bool
    {
        return $this->vinForceShipment ?? false;
    }

    /**
     * Set the order ID
     *
     * @param string $orderId
     * @return void
     */
    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
    }

    /**
     * Get the order ID
     *
     * @return string|null
     */
    public function getOrderId(): ?string
    {
        return $this->orderId;
    }
}
