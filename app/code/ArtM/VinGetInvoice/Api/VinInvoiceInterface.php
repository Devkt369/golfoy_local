<?php

namespace ArtM\VinGetInvoice\Api;

use ArtM\VinGetInvoice\Api\Data\VinInvoiceItemInterface;

interface VinInvoiceInterface
{
    /**
     * Save VIN invoice number and associated items.
     *
     * @param int $invoiceId The ID of the invoice to update.
     * @param int $vinInvoiceNumber The VIN invoice number to save.
     * @param VinInvoiceItemInterface[] $itemIdsQty An array of invoice items. Each item should implement the VinInvoiceItemInterface.
     * @return string
     */
    public function saveVinInvoiceNumber(int $orderId, string $vinInvoiceNumber, array $itemIdsQty, string $dateTime): string;
}
