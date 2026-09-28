<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Renders is_mapped as a coloured badge in the grid.
 * Does NOT try to set a _label suffix — the column component handles its own display.
 */
class IsMapped extends Column
{
    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $columnName = $this->getData('name');

        foreach ($dataSource['data']['items'] as &$item) {
            $isMapped = isset($item[$columnName]) ? (int)$item[$columnName] : 0;

            $item[$columnName . '_html'] = $isMapped
                ? '<span style="color:#27ae60;font-weight:600;">&#10003; Mapped</span>'
                : '<span style="color:#e74c3c;font-weight:600;">&#10007; Not Mapped</span>';

            // Keep the raw value intact so the inline-edit select still works
            $item[$columnName] = $isMapped;
        }

        return $dataSource;
    }
}
