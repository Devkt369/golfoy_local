<?php

namespace ArtM\ShipmentTracking\Model\Order;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Shipment\Track;
use ArtM\ShipmentTracking\Model\Service\CheckDeliveryStatus;
use ArtM\ShipmentTracking\Model\Service\ShipmentTrackingStatus;

class UpdateOrderStatus
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var CheckDeliveryStatus
     */
    protected $_checkDeliveryStatus;

    /**
     * @var ShipmentTrackingStatus
     */
    protected $shipmentTrackingStatus;

    /**
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        CheckDeliveryStatus $_checkDeliveryStatus,
        ShipmentTrackingStatus $shipmentTrackingStatus,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->_checkDeliveryStatus = $_checkDeliveryStatus;
        $this->shipmentTrackingStatus = $shipmentTrackingStatus;
        $this->orderRepository = $orderRepository;
    }

    /**
     * Update order status to delivered
     * 
     * @param Order|int|string $orderParam (Can be Order object or Entity ID)
     * @return bool
     */
    public function updateToDelivered($orderParam)
    {
        try {
            // 1. Determine if we have an ID or an Object
            if ($orderParam instanceof Order) {
                $order = $orderParam;
            } else {
                // If it's just an ID, load the order object
                $order = $this->orderRepository->get((int)$orderParam);
            }

            // 2. Check if already delivered to prevent redundant saving
            if ($order->getStatus() === 'delivered') {
                return true;
            }

            // 3. Set the status and state
            // Note: 'complete' is the standard state for 'delivered' status
            $order->setStatus('delivered');
            $order->addCommentToStatusHistory(
                __('Order status updated to Delivered automatically via Shipment tracking module.'),
                'delivered'
            );

            // 4. Save via Repository
            $this->orderRepository->save($order);

            return true;
        } catch (\Exception $e) {
            // Log the error if the order ID doesn't exist
            return false;
        }
    }

    /**
     * Check if ALL shipments/tracks of an order are delivered
     */
    public function checkOrderShipmentStatus($title, $trackNumber, $orderId, Track $track = null)
    {
        // We don't update here anymore. Just return whether this track is delivered.
        $trackingResponse = $this->_checkDeliveryStatus->processTracking($title, $trackNumber, $orderId);
        $response = json_decode($trackingResponse, true);
        $normalized = $this->shipmentTrackingStatus->normalizeResponse($title, $trackingResponse);

        if ($track) {
            $this->shipmentTrackingStatus->saveTrackResponse($track, $trackingResponse);
        }

        return !empty($normalized['is_delivered']) || $this->hasDeliveredStatus(json_decode($trackingResponse, true));
    }

    /**
     * 
     *
     * @param string $orderId
     * @return bool
     */
    public function updateOrderIfAllDelivered($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        // Skip if already delivered
        if ($order->getStatus() === 'delivered') {
            return true;
        }

        $allTracks = $order->getTracksCollection(); // or load from shipment tracks

        if ($allTracks->getSize() === 0) {
            return false;
        }

        $allDelivered = true;

        foreach ($allTracks as $track) {
            $title = strtolower(trim($track->getTitle() ?? ''));
            $trackNumber = $track->getTrackNumber();

            if (empty($trackNumber)) {
                $allDelivered = false;
                continue;
            }

            $isDelivered = $this->checkOrderShipmentStatus($title, $trackNumber, $orderId, $track);

            if (!$isDelivered) {
                $allDelivered = false;
            }
        }

        if ($allDelivered) {
            $this->updateToDelivered($orderId);
            return true;
        }

        return false;
    }

    /**
     *
     * @param string $data
     * @return boolean
     */
    private function hasDeliveredStatus($data)
    {
        if (!is_array($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (
                in_array(strtolower($key), ['status', 'del_status']) &&
                is_string($value) &&
                strtolower(trim($value)) === 'delivered'
            ) {
                return true;
            }

            if (is_array($value) && $this->hasDeliveredStatus($value)) {
                return true;
            }
        }

        return false;
    }
}
