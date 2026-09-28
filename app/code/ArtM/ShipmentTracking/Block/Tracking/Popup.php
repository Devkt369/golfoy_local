<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Block\Tracking;

use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Stdlib\DateTime\DateTimeFormatterInterface;
use ArtM\ShipmentTracking\Model\Service\CheckDeliveryStatus;
use ArtM\ShipmentTracking\Model\TrackingStatusMappingRepository;
use ArtM\ShipmentTracking\Model\Service\TrackingStatusAutoMapper;

class Popup extends \Magento\Shipping\Block\Tracking\Popup
{
    public function __construct(
        Context $context,
        Registry $registry,
        DateTimeFormatterInterface $dateTimeFormatter,
        protected CheckDeliveryStatus $checkDeliveryStatus,
        protected TrackingStatusMappingRepository $mappingRepository,
        protected TrackingStatusAutoMapper $autoMapper,
        array $data = []
    ) {
        parent::__construct($context, $registry, $dateTimeFormatter, $data);
    }

    public function getCheckDeliveryStatus(): CheckDeliveryStatus
    {
        return $this->checkDeliveryStatus;
    }

    /**
     * Resolve a carrier status to a customer-facing label.
     *
     * Flow:
     *   1. Normalize the raw status
     *   2. Look up in the DB mapping table (exact match → keyword match)
     *   3. If found and active  → return display label ("Do not show" = return '')
     *   4. If NOT found        → auto-insert row with is_mapped=0 so admin sees it
     *                            → return ucwords fallback so storefront doesn't break
     */
    public function getMappedStatus(string $status, string $carrierName = '', string $trackingNumber = ''): string
    {
        $normalizedStatus = $this->normalizeStatus($status);

        $label = $this->mappingRepository->resolve($normalizedStatus);

        if ($label !== null) {
            return $label === 'Do not show' ? '' : $label;
        }

        $this->autoMapper->insertIfNew($normalizedStatus, $carrierName, $trackingNumber);

        return ucwords(str_replace('_', ' ', $status));
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $status = str_replace('_', ' ', $status);
        return (string)preg_replace('/\s+/', ' ', $status);
    }
}
