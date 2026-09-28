<?php
namespace ArtM\MassProducts\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class OperationTypeBadge extends Column
{
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as & $item) {
            $value = (string)($item['operation_type'] ?? 'create');
            $label = ucfirst($value);
            $bg = $value === 'update' ? '#2980b9' : '#2f7d32';
            $item[$this->getData('name')] = '<span style="display:inline-block;padding:2px 8px;border-radius:12px;color:#fff;background:' . $bg . ';">' . $label . '</span>';
        }

        return $dataSource;
    }
}
