<?php

/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magestore\ReportSuccess\Model\Export\IncomingStock;

use Magestore\ReportSuccess\Model\Export\AbstractConvertToCsv;

/**
 * Class ConvertToCsv
 *
 * Used for custom export csv grid stock income
 */
class ConvertToCsv extends AbstractConvertToCsv
{
    /**
     * @var string
     */
    protected $reportTitle = 'INCOMING STOCK REPORT';
    /**
     * @var array
     */
    protected $totalData = [
        'incoming_stock' => 0,
        'overdue_incoming_stock' => 0,
        'total_cost' => 0
    ];
    /**
     * @var array
     */
    protected $priceTotal = [
        'total_cost'
    ];
    /**
     * @var array
     */
    protected $needExplodeItem = ['purchase_order', 'supplier'];
    /**
     * @var array
     */
    protected $decimalField = ['qty_on_hand','overdue_incoming_stock','incoming_stock'];

    /**
     * Abstract function
     *
     * Initialize options
     */
    public function setOptions()
    {
        $options = $this->metadataProvider->getOptions();
        $options['supplier'] = $this->supplier->toOptionListArray();
        $options['purchase_order'] = $this->purchaseOrder->toOptionListArray();
        $filters = $this->_request->getParam('filters');
        if (isset($filters['supplier']) &&
            $filters['supplier'] &&
            isset($options['supplier'][$filters['supplier']])
        ) {
            $options['supplier'] = [$filters['supplier']=>$options['supplier'][$filters['supplier']]];
        }
        $this->options = $options;
    }

    /**
     * Abstract function
     *
     * Increase total
     *
     * @param array $item
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    public function increaseTotals($item)
    {
        foreach ($this->totalData as $key => $value) {
            if (isset($item[$key]) && $item[$key]) {
                $this->totalData[$key] += $item[$key];
            }
        }
    }
}
