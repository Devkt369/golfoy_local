<?php

namespace ArtM\TradeIn\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class ViewInvoiceFile extends Column
{
    public function prepareDataSource(array $dataSource)
    {
    if (isset($dataSource['data']['items'])) {
        foreach ($dataSource['data']['items'] as &$item) {
            if (!empty($item['invoice_file']) && filter_var($item['invoice_file'], FILTER_VALIDATE_URL)) {
                $fileUrl = $item['invoice_file'];
                $item[$this->getData('name')] = '<a href="' . $fileUrl . '" target="_blank">View</a>';
            } else {
                // Optionally: unset the column if no file exists
                unset($item[$this->getData('name')]);
            }
        }
    }

        return $dataSource;
    }
}
