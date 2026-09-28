<?php
/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\Webpos\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\Webpos\Model\Checkout\PosOrder;

/**
 * Class \Magestore\Webpos\Ui\Component\Listing\Column\DetailErrorActions
 *
 * Web POS User Actions
 * Methods:
 *  prepareDataSource
 *
 * @module      Webpos
 * @author      Magestore Developer
 */
class DetailErrorActions extends Column
{
    /**
     * Prepare DataSource
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                $errorMessage = '';
                if ($item['additional_data'] !== '') {
                    $invoiceError = strpos($item['additional_data'], 'invoice');
                    $shipmentError = strpos($item['additional_data'], 'shipment');

                    if ($invoiceError !== false) {
                        $errorMessage = __('Unsuccessful invoice creation');
                    }
                    if ($shipmentError !== false) {
                        if ($errorMessage !== '') {
                            $errorMessage .= "<br>" . __('Unsuccessful shipment creation');
                        } else {
                            $errorMessage = __('Unsuccessful shipment creation');
                        }
                    }
                } else {
                    if ($item['status'] === PosOrder::STATUS_PENDING) {
                        $errorMessage = '';
                    } else {
                        $errorMessage = __('Cannot be made into Magento’s order');
                    }
                }
                $item['additional_data'] = $errorMessage;
            }
        }
        return $dataSource;
    }
}
