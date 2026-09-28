<?php

namespace ArtM\VinGetInvoice\Model;

use ArtM\VinGetInvoice\Api\VinInvoiceInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use ArtM\VinGetInvoice\Api\Data\VinInvoiceItemInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class VinInvoice implements VinInvoiceInterface
{
    protected $invoiceRepository;
    protected $resourceConnection;
    protected $invoiceCollectionFactory;
    protected $orderRepository;
    protected $json;
    private $timezone;


    public function __construct(
        InvoiceRepositoryInterface $invoiceRepository,
        ResourceConnection $resourceConnection,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        OrderRepositoryInterface $orderRepository,
        Json $json,
        TimezoneInterface $timezone
    ) {
        $this->invoiceRepository = $invoiceRepository;
        $this->resourceConnection = $resourceConnection;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->orderRepository = $orderRepository;
        $this->json = $json;
        $this->timezone = $timezone;
    }

    public function saveVinInvoiceNumber(int $orderId, string $vinInvoiceNumber, array $itemIdsQty, string $dateTime): string
    {
        $connection = $this->resourceConnection->getConnection();
        $salesInvoiceTable = $connection->getTableName('sales_invoice');
        $historyTable = $connection->getTableName('vin_invoice_fetch_history');

        $items = $itemIdsQty;
        $providedItems = [];

        foreach ($items as $item) {
            if (!$item instanceof VinInvoiceItemInterface) {
                continue;
            }

            $itemId = (int)$item->getId();
            $qty = (int)$item->getQty();

            // ✅ Check if item is a configurable parent later, during invoice match
            $providedItems[$itemId] = $qty;
        }

        if (!$orderId || !$vinInvoiceNumber || empty($providedItems) || !$this->isValidDateTimeFormat($dateTime)) {
            foreach ($providedItems as $itemId => $qty) {
                $this->logHistory($connection, $historyTable, $orderId, $this->json->serialize($providedItems), 'Invalid input', $itemId, $vinInvoiceNumber);
            }
            return "Invalid input data.";
        }

        $invoiceCollection = $this->invoiceCollectionFactory->create()
            ->addFieldToFilter('order_id', $orderId);

        foreach ($invoiceCollection as $invoice) {
            if ($invoice->getData('vin_invoice_number')) {
                foreach ($providedItems as $itemId => $qty) {
                    $this->logHistory(
                        $connection,
                        $historyTable,
                        $orderId,
                        $this->json->serialize($providedItems),
                        'VIN invoice number already exists on invoice ID: ' . $invoice->getId(),
                        $itemId,
                        $vinInvoiceNumber
                    );
                }
                continue;
            }

            $invoiceItems = [];

            foreach ($invoice->getAllItems() as $item) {
                $orderItem = $item->getOrderItem();

                // ✅ Skip configurable parent
                if ($orderItem->getProductType() === 'configurable') {
                    continue;
                }

                $parentItem = $orderItem->getParentItem();

                // ✅ Use parent item's ID if it's a configurable parent
                $keyItemId = $parentItem && $parentItem->getProductType() === 'configurable'
                    ? (int)$parentItem->getItemId()
                    : (int)$item->getOrderItemId();

                $invoiceItems[$keyItemId] = (int)$item->getQty();
            }

            if ($invoiceItems === $providedItems) {
                $connection->update(
                    $salesInvoiceTable,
                    [
                        'vin_invoice_number' => $vinInvoiceNumber,
                        'vin_invoice_date_time' => $dateTime
                    ],
                    ['entity_id = ?' => $invoice->getId()]
                );

                foreach ($providedItems as $itemId => $qty) {
                    $this->logHistory(
                        $connection,
                        $historyTable,
                        $orderId,
                        $this->json->serialize($providedItems),
                        'VIN invoice updated on invoice ID: ' . $invoice->getId(),
                        $itemId,
                        $vinInvoiceNumber,
                        $dateTime
                    );
                }

                return "VIN Invoice number saved successfully.";
            }
        }

        foreach ($providedItems as $itemId => $qty) {
            $this->logHistory(
                $connection,
                $historyTable,
                $orderId,
                $this->json->serialize($providedItems),
                'No matching invoice found',
                $itemId,
                $vinInvoiceNumber,
                $dateTime
            );
        }

        return "No matching invoice found. Check item IDs and quantities.";
    }


    

    private function logHistory($connection, $tableName, $orderId, $requestData, $responseData, $itemId, $vinInvoiceNumber)
    {
        $connection->insert($tableName, [
            'order_id' => $orderId,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'item_id' => $itemId,
            'vin_invoice_number' => $vinInvoiceNumber,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
        ]);
    }

    public function isValidDateTimeFormat($datetime)
    {
        $format = 'd-m-Y'; 
        $dt = \DateTime::createFromFormat($format, $datetime);
        return $dt && $dt->format($format) === $datetime;
    }
}
