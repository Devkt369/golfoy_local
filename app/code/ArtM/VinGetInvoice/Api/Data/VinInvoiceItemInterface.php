<?php

namespace ArtM\VinGetInvoice\Api\Data;

interface VinInvoiceItemInterface
{
    /**
     * Get item ID.
     *
     * @return int|null
     */
    public function getId();

    /**
     * Set item ID.
     *
     * @param int|null $id
     * @return $this
     */
    public function setId(?int $id);

    /**
     * Get quantity.
     *
     * @return int|null
     */
    public function getQty();

    /**
     * Set quantity.
     *
     * @param int|null $qty
     * @return $this
     */
    public function setQty(?int $qty);
}