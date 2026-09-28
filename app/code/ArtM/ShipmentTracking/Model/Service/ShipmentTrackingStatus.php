<?php

namespace ArtM\ShipmentTracking\Model\Service;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Shipment\Track;
use ArtM\ShipmentTracking\Block\Tracking\Popup;

class ShipmentTrackingStatus
{
    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * @var Json
     */
    private $json;

    protected $popupBlock;

    public function __construct(
        ResourceConnection $resource,
        Json $json,
        Popup $popupBlock
    ) {
        $this->resource = $resource;
        $this->json = $json;
        $this->popupBlock = $popupBlock;
    }

    public function saveTrackResponse(Track $track, $response)
    {
        if (!$track->getId()) {
            return;
        }

        $connection = $this->resource->getConnection();
        $table = $this->resource->getTableName('sales_shipment_track');
        $normalized = $this->normalizeResponse((string)$track->getTitle(), $response);

        $now = gmdate('Y-m-d H:i:s');
        $shipmentData = $this->getShipmentData((int)$track->getParentId());

        $mappedStatus = $this->popupBlock->getMappedStatus($normalized['status'], (string)$track->getTitle(), (string)$track->getTrackNumber());
        $data = [
            'tracking_status' => $mappedStatus,
            'tracking_response' => $normalized['is_delivered'] ? null : $this->normalizeResponseJson($response),
            'shipment_items_json' => $this->json->serialize($shipmentData['items']),
            'order_created_at' => $shipmentData['order_created_at'],
            'shipment_created_at' => $shipmentData['shipment_created_at'],
            'current_status_at' => $normalized['current_status_at'],
            'out_for_delivery_at' => $normalized['out_for_delivery_at'],
            'delivered_at' => $normalized['delivered_at'],
            'last_checked_at' => $now,
        ];

        $result = $connection->update(
            $table,
            $data,
            ['entity_id = ?' => (int)$track->getId()]
        );
    }

    public function getItemTrackingDetails(OrderItem $item)
    {
        $connection = $this->resource->getConnection();
        $trackTable = $this->resource->getTableName('sales_shipment_track');
        $shipmentTable = $this->resource->getTableName('sales_shipment');
        $shipmentItemTable = $this->resource->getTableName('sales_shipment_item');

        $select = $connection->select()
            ->from(
                ['track' => $trackTable],
                [
                    'track_id' => 'entity_id',
                    'shipment_id' => 'parent_id',
                    'track_number',
                    'carrier_title' => 'title',
                    'tracking_status',
                    'tracking_response',
                    'order_created_at',
                    'shipment_created_at',
                    'current_status_at',
                    'out_for_delivery_at',
                    'delivered_at',
                    'last_checked_at',
                    'track_updated_at' => 'updated_at',
                ]
            )
            ->joinInner(
                ['shipment' => $shipmentTable],
                'shipment.entity_id = track.parent_id',
                ['shipment_increment_id' => 'increment_id']
            )
            ->joinInner(
                ['shipment_item' => $shipmentItemTable],
                'shipment_item.parent_id = shipment.entity_id',
                ['qty_shipped' => 'qty']
            )
            ->where('shipment_item.order_item_id = ?', (int)$item->getId())
            ->order(['track.last_checked_at DESC', 'track.updated_at DESC']);

        $rows = $connection->fetchAll($select);

        foreach ($rows as &$row) {
            if (empty($row['tracking_status']) && !empty($row['tracking_response'])) {
                $normalized = $this->normalizeResponse((string)$row['carrier_title'], $row['tracking_response']);
                $row['tracking_status'] = $normalized['status'];
                $row['current_status_at'] = $row['current_status_at'] ?: $normalized['current_status_at'];
                $row['out_for_delivery_at'] = $row['out_for_delivery_at'] ?: $normalized['out_for_delivery_at'];
                $row['delivered_at'] = $row['delivered_at'] ?: $normalized['delivered_at'];
            }

            if (empty($row['tracking_status'])) {
                $row['tracking_status'] = !empty($row['delivered_at']) ? 'Delivered' : 'Shipped';
            }
        }
        unset($row);

        return $rows;
    }

    public function getItemCurrentStatus(OrderItem $item)
    {
        $details = $this->getItemTrackingDetails($item);

        if (empty($details)) {
            return null;
        }

        foreach ($details as $detail) {
            if (!empty($detail['tracking_status'])) {
                return $detail;
            }
        }

        return reset($details);
    }

    public function normalizeResponse($title, $response)
    {
        $data = $this->decodeResponse($response);

        if (!is_array($data)) {
            return [
                'status' => 'Shipped',
                'current_status_at' => null,
                'out_for_delivery_at' => null,
                'delivered_at' => null,
                'is_delivered' => false,
            ];
        }

        if (isset($data['ShipmentData'][0]['Shipment'])) {
            return $this->normalizeDelhivery($data['ShipmentData'][0]['Shipment']);
        }

        if (isset($data['checkpoints'])) {
            return $this->normalizeBlueDart($data);
        }

        if (isset($data['orderInformation']) || isset($data['statuses'])) {
            return $this->normalizeShreeMaruti($data);
        }

        if (
            isset($data['data'][0]['tracking_details']) &&
            isset($data['data'][0]['booking_details'])
        ) {
            return $this->normalizeIndiaPost($data['data'][0]);
            }
            
        return $this->normalizeGeneric($data);
    }

    private function normalizeDelhivery(array $shipment)
    {
        $currentStatus = $shipment['Status'] ?? [];
        $statusText = $this->combineStatusText(
            $currentStatus['Status'] ?? '',
            $currentStatus['Instructions'] ?? ''
        );
        $currentStatusAt = $this->formatDate($currentStatus['StatusDateTime'] ?? null);
        $outForDeliveryAt = null;
        $deliveredAt = null;

        foreach (($shipment['Scans'] ?? []) as $scan) {
            $scanDetail = $scan['ScanDetail'] ?? [];
            $text = $this->combineStatusText(
                $scanDetail['Scan'] ?? '',
                $scanDetail['Instructions'] ?? ''
            );
            $date = $this->formatDate($scanDetail['ScanDateTime'] ?? null);

            if ($this->isOutForDeliveryText($text) && !$outForDeliveryAt) {
                $outForDeliveryAt = $date;
            }

            if ($this->isDeliveredText($text) && !$deliveredAt) {
                $deliveredAt = $date;
            }
        }

        if ($this->isDeliveredText($statusText)) {
            $deliveredAt = $deliveredAt ?: $this->formatDate($currentStatus['StatusDateTime'] ?? null);
        }

        if ($this->isOutForDeliveryText($statusText)) {
            $outForDeliveryAt = $outForDeliveryAt ?: $this->formatDate($currentStatus['StatusDateTime'] ?? null);
        }

        return $this->buildNormalized($statusText, $currentStatusAt, $outForDeliveryAt, $deliveredAt);
    }

    private function normalizeBlueDart(array $data)
    {
        $checkpoints = $data['checkpoints'] ?? [];
        $currentStatus = $checkpoints[0] ?? [];
        $statusText = $this->firstNonEmpty([
            $currentStatus['tag'] ?? null,
            $currentStatus['message'] ?? null,
        ]);
        $currentStatusAt = $this->formatDate($currentStatus['checkpoint_time'] ?? null);
        $outForDeliveryAt = null;
        $deliveredAt = null;

        foreach ($checkpoints as $checkpoint) {
            $text = trim(($checkpoint['tag'] ?? '') . ' ' . ($checkpoint['message'] ?? ''));
            $date = $this->formatDate($checkpoint['checkpoint_time'] ?? null);

            if ($this->isOutForDeliveryText($text) && !$outForDeliveryAt) {
                $outForDeliveryAt = $date;
            }

            if ($this->isDeliveredText($text) && !$deliveredAt) {
                $deliveredAt = $date;
            }
        }

        return $this->buildNormalized($statusText, $currentStatusAt, $outForDeliveryAt, $deliveredAt);
    }

    private function normalizeIndiaPost(array $data)
    {
        $tracking = $data['tracking_details'] ?? [];
        $delivery = $data['del_status'] ?? [];

        $latest = $tracking[0] ?? [];

        $statusText = $delivery['del_status']
            ?? ($latest['event'] ?? '');

        $currentStatusAt = $this->formatDate($latest['date'] ?? null);

        $outForDeliveryAt = null;
        $deliveredAt = null;

        foreach ($tracking as $event) {
            $text = $event['event'] ?? '';
            $date = $this->formatDate($event['date'] ?? null);

            if (
                stripos($text, 'taken out for delivery') !== false &&
                !$outForDeliveryAt
            ) {
                $outForDeliveryAt = $date;
            }

            if (
                strtolower((string)$text) == 'delivered' &&
                !$deliveredAt
            ) {
                $deliveredAt = $date;
            }
        }

        return $this->buildNormalized(
            $statusText,
            $currentStatusAt,
            $outForDeliveryAt,
            $deliveredAt
        );
    }

    private function normalizeShreeMaruti(array $data)
    {
        $statuses = $data['statuses'] ?? [];
        $currentStatus = $statuses[0] ?? [];
        $statusText = $this->combineStatusText(
            $currentStatus['status'] ?? ($data['orderInformation']['status'] ?? ''),
            $currentStatus['subcategory'] ?? ''
        );
        $currentStatusAt = $this->formatDate($currentStatus['statusTimestamp'] ?? null);
        $outForDeliveryAt = null;
        $deliveredAt = null;

        foreach ($statuses as $status) {
            $text = $this->combineStatusText(
                $status['status'] ?? '',
                $status['subcategory'] ?? ''
            );
            $date = $this->formatDate($status['statusTimestamp'] ?? null);

            if ($this->isOutForDeliveryText($text) && !$outForDeliveryAt) {
                $outForDeliveryAt = $date;
            }

            if ($this->isDeliveredText($text) && !$deliveredAt) {
                $deliveredAt = $date;
            }
        }

        return $this->buildNormalized($statusText, $currentStatusAt, $outForDeliveryAt, $deliveredAt);
    }

    private function normalizeGeneric(array $data)
    {
        $statusText = $this->findFirstValueByKeys($data, ['status', 'tag', 'scan', 'subcategory', 'instructions']);
        $currentStatusAt = $this->findFirstDateValue($data);
        $outForDeliveryAt = null;
        $deliveredAt = null;

        $this->walkTrackingData($data, function ($value, $nearDate) use (&$outForDeliveryAt, &$deliveredAt) {
            if (!is_string($value)) {
                return;
            }

            if ($this->isOutForDeliveryText($value) && !$outForDeliveryAt) {
                $outForDeliveryAt = $this->formatDate($nearDate);
            }

            if ($this->isDeliveredText($value) && !$deliveredAt) {
                $deliveredAt = $this->formatDate($nearDate);
            }
        });

        return $this->buildNormalized($statusText, $currentStatusAt, $outForDeliveryAt, $deliveredAt);
    }

    private function buildNormalized($statusText, $currentStatusAt, $outForDeliveryAt, $deliveredAt)
    {
        $status = $this->humanizeStatus($statusText);
        $isDelivered = $this->isDeliveredText($statusText) || !empty($deliveredAt);

        if ($isDelivered) {
            $status = 'Delivered';
        } elseif ($this->isReturnText($statusText)) {
            $status = 'RTO';
        } elseif ($this->isOutForDeliveryText($statusText)) {
            $status = 'Out for delivery';
        } elseif ($this->isInTransitText($statusText)) {
            $status = 'In transit';
        } elseif ($this->isPendingText($statusText)) {
            $status = 'Pending';
        } elseif (!$status) {
            $status = 'Shipped';
        }

        return [
            'status' => $status,
            'current_status_at' => $currentStatusAt,
            'out_for_delivery_at' => $outForDeliveryAt,
            'delivered_at' => $deliveredAt,
            'is_delivered' => $isDelivered,
        ];
    }

    private function getShipmentData($shipmentId)
    {
        if (!$shipmentId) {
            return [
                'order_created_at' => null,
                'shipment_created_at' => null,
                'items' => [],
            ];
        }

        $connection = $this->resource->getConnection();
        $shipmentTable = $this->resource->getTableName('sales_shipment');
        $orderTable = $this->resource->getTableName('sales_order');
        $shipmentItemTable = $this->resource->getTableName('sales_shipment_item');
        $orderItemTable = $this->resource->getTableName('sales_order_item');

        $shipmentSelect = $connection->select()
            ->from(['shipment' => $shipmentTable], ['shipment_created_at' => 'created_at'])
            ->joinLeft(
                ['order' => $orderTable],
                'order.entity_id = shipment.order_id',
                ['order_created_at' => 'created_at']
            )
            ->where('shipment.entity_id = ?', $shipmentId);
        $shipment = $connection->fetchRow($shipmentSelect) ?: [];

        $itemsSelect = $connection->select()
            ->from(
                ['shipment_item' => $shipmentItemTable],
                [
                    'shipment_item_id' => 'entity_id',
                    'order_item_id',
                    'sku',
                    'name',
                    'qty_shipped' => 'qty',
                ]
            )
            ->joinLeft(
                ['order_item' => $orderItemTable],
                'order_item.item_id = shipment_item.order_item_id',
                ['product_id']
            )
            ->where('shipment_item.parent_id = ?', $shipmentId);

        return [
            'order_created_at' => $shipment['order_created_at'] ?? null,
            'shipment_created_at' => $shipment['shipment_created_at'] ?? null,
            'items' => $connection->fetchAll($itemsSelect),
        ];
    }

    private function normalizeResponseJson($response)
    {
        $data = $this->decodeResponse($response);

        if (is_array($data)) {
            return $this->json->serialize($data);
        }

        return (string)$response;
    }

    private function decodeResponse($response)
    {
        if (is_array($response)) {
            return $response;
        }

        if (!is_string($response) || trim($response) === '') {
            return null;
        }

        try {
            $decoded = $this->json->unserialize($response);
        } catch (\InvalidArgumentException $e) {
            $decoded = json_decode($response, true);
        }

        return is_array($decoded) ? $decoded : null;
    }

    private function walkTrackingData(array $data, callable $callback, $nearDate = null)
    {
        foreach ($data as $key => $value) {
            $nextNearDate = $nearDate;

            if ($this->isDateKey($key) && (is_scalar($value) || $value === null)) {
                $nextNearDate = $value;
            }

            if (is_array($value)) {
                $this->walkTrackingData($value, $callback, $nextNearDate);
                continue;
            }

            $callback($value, $nextNearDate);
        }
    }

    private function findFirstValueByKeys(array $data, array $keys)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $found = $this->findFirstValueByKeys($value, $keys);
                if ($found) {
                    return $found;
                }
                continue;
            }

            if (in_array(strtolower((string)$key), $keys, true) && is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private function findFirstDateValue(array $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $found = $this->findFirstDateValue($value);
                if ($found) {
                    return $found;
                }
                continue;
            }

            if ($this->isDateKey($key)) {
                $date = $this->formatDate($value);

                if ($date) {
                    return $date;
                }
            }
        }

        return null;
    }

    private function firstNonEmpty(array $values)
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    private function combineStatusText($status, $message)
    {
        $parts = [];

        foreach ([$status, $message] as $part) {
            $part = trim((string)$part);

            if ($part !== '' && !in_array($part, $parts, true)) {
                $parts[] = $part;
            }
        }

        return implode(' - ', $parts);
    }

    private function humanizeStatus($status)
    {
        $status = trim((string)$status);

        if ($status === '') {
            return '';
        }

        $status = str_replace(['_', '-'], ' ', $status);
        $status = preg_replace('/(?<!^)([A-Z])/', ' $1', $status);
        $status = preg_replace('/\s+/', ' ', $status);

        return ucfirst(strtolower($status));
    }

    private function isDeliveredText($text)
    {
        return strtolower((string)$text) === 'delivered';
    }

    private function isReturnText($text)
    {
        $text = strtolower((string)$text);

        return strpos($text, 'rto') !== false
            || strpos($text, 'return') !== false
            || strpos($text, 'reverse') !== false;
    }

    private function isOutForDeliveryText($text)
    {
        $text = strtolower((string)$text);

        return strpos($text, 'out for delivery') !== false
            || strpos($text, 'outfordelivery') !== false
            || strpos($text, 'out of delivery') !== false
            || strpos($text, 'ofd') !== false;
    }

    private function isInTransitText($text)
    {
        $text = strtolower((string)$text);

        return strpos($text, 'in transit') !== false
            || strpos($text, 'intransit') !== false
            || strpos($text, 'in_transit') !== false
            || strpos($text, 'inscan') !== false
            || strpos($text, 'outscan') !== false;
    }

    private function isPendingText($text)
    {
        return stripos((string)$text, 'pending') !== false;
    }

    private function isDateKey($key)
    {
        $key = strtolower((string)$key);

        return strpos($key, 'date') !== false
            || strpos($key, 'time') !== false
            || strpos($key, 'timestamp') !== false;
    }

    private function formatDate($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            $timestamp = (int)$value;

            if ($timestamp > 9999999999) {
                $timestamp = (int)floor($timestamp / 1000);
            }

            return gmdate('Y-m-d H:i:s', $timestamp);
        }

        $timestamp = strtotime((string)$value);

        if (!$timestamp) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }
}
