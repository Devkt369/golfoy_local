<?php

namespace ArtM\ShipmentTracking\Block;

use Magento\Framework\View\Element\Template;

class ShipmentTimeline extends Template
{
    public function getTimelineData($shipment)
    {
        $tracks = $shipment->getTracksCollection();

        if (!$tracks->count()) {
            return [];
        }

        $track = $tracks->getFirstItem();

        return [
            'order_created_at'    => $track->getData('order_created_at'),
            'shipment_created_at' => $track->getData('shipment_created_at'),
            'out_for_delivery_at' => $track->getData('out_for_delivery_at'),
            'delivered_at'        => $track->getData('delivered_at'),
            'tracking_status'     => $track->getData('tracking_status')
        ];
    }
}