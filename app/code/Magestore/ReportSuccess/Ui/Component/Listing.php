<?php
/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
namespace Magestore\ReportSuccess\Ui\Component;
/**
 * Class Listing
 * @package Magestore\ReportSuccess\Ui\Component
 */
class Listing extends \Magento\Ui\Component\Listing
{
    /**
     * Render component to HTML
     * @param string $contentType
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function render(string $contentType = '')
    {
        $currentUrl = $this->context->getUrl('*/*/*');
        if (false === strpos($currentUrl, 'saveStockByLocation')) {
            return parent::render($contentType);
        }
        // Return columns only
        $columns = $this->components['inventory_report_stockbylocation_columns']->getChildComponents();
        $data = [];
        foreach ($columns as $key => $column) {
            $data[$key] = $column->getData();
            $data[$key]['type'] = $column->getComponentName();
        }
        return json_encode($data);
    }
}
