<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Seeds the artm_tracking_status_mapping table with the original hardcoded
 * status mappings so admins have a starting point.
 */
class SeedTrackingStatusMappings implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    public function apply(): self
    {
        $this->moduleDataSetup->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        $tableName  = $this->moduleDataSetup->getTable('artm_tracking_status_mapping');

        $mappings = $this->getDefaultMappings();

        foreach ($mappings as $row) {
            // Use INSERT IGNORE so re-running the patch is safe
            $connection->insertOnDuplicate(
                $tableName,
                $row,
                ['display_label', 'is_active', 'is_mapped']
            );
        }

        $this->moduleDataSetup->endSetup();

        return $this;
    }

    /**
     * Returns the original hardcoded mapping rows, normalised.
     */
    private function getDefaultMappings(): array
    {
        $data = [
            // ORDER INITIATION / CONFIRMATION
            ['manifest uploaded',                       'Order confirmed'],
            ['online shipment booked',                  'Order confirmed'],
            ['ready for dispatch',                      'Order confirmed'],
            ['order confirmed',                         'Order confirmed'],

            // PICKUP
            ['pickup scheduled',                        'Pickup scheduled'],
            ['pickup employee is out to p/u shipment',  'Pickup scheduled'],
            ['pickup employee is out',                  'Pickup scheduled'],
            ['shipment picked up',                      'Picked up by courier'],
            ['item booked',                             'Picked up by courier'],

            // COURIER FACILITY ARRIVALS
            ['shipment recieved at origin center',      'Reached courier facility'],
            ['shipment arrived at hub',                 'Reached destination facility'],
            ['shipment received at facility',           'Reached destination facility'],
            ['item received at destination',            'Reached destination facility'],
            ['inscanned at cp',                         'Reached destination facility'],
            ['shipment arrived',                        'Reached destination facility'],

            // IN TRANSIT
            ['added to bag',                            'In transit'],
            ['bag added to trip',                       'In transit'],
            ['trip arrived',                            'In transit'],
            ['vehicle departed',                        'In transit'],
            ['bag received at facility',                'In transit'],
            ['shipment further connected',              'In transit'],
            ['inscan at hub',                           'In transit'],
            ['outscan at hub',                          'In transit'],
            ['outscan to cp',                           'In transit'],
            ['item bagged',                             'In transit'],
            ['item dispatched',                         'In transit'],
            ['item received',                           'In transit'],
            ['item invoiced to bo',                     'In transit'],

            // OUT FOR DELIVERY
            ['out for delivery',                        'Out for delivery'],
            ['shipment out for delivery',               'Out for delivery'],
            ['taken out for delivery',                  'Out for delivery'],

            // DELIVERY RESCHEDULED / ATTEMPTS
            ['delivery scheduled for next working day', 'Delivery rescheduled'],
            ['consignee not available',                 'Delivery Attempt Failed'],

            // DELIVERED
            ['delivered to consignee',                  'Delivered'],
            ['shipment delivered',                      'Delivered'],
            ['item delivered(addressee)',               'Delivered'],
            ['delivered',                               'Delivered'],

            // DO NOT SHOW
            ['weight captured',                         'Do not show'],
            ['bag removed',                             'Do not show'],
        ];

        $rows = [];
        foreach ($data as [$raw, $label]) {
            $rows[] = [
                'raw_status'    => $raw,
                'display_label' => $label,
                'carrier_name'  => '',
                'is_active'     => 1,
                'is_mapped'     => 1,
            ];
        }

        return $rows;
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }
}
