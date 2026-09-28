<?php

namespace ArtM\BrandInventory\Controller\Adminhtml\Data;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Controller\Result\RawFactory;

class ViewManualMatches extends Action
{
    protected $resource;
    protected $resultRawFactory;

    public function __construct(
        Context $context,
        RawFactory $resultRawFactory,
        ResourceConnection $resource
    ) {
        parent::__construct($context);
        $this->resultRawFactory = $resultRawFactory;
        $this->resource = $resource;
    }

    public function execute()
    {
        try {
            $connection = $this->resource->getConnection();
            $table = $this->resource->getTableName('artm_brandinventory_manual_match');

            $rows = $connection->fetchAll("SELECT * FROM {$table} ORDER BY created_date DESC");

            $html = '<!doctype html><html><head><meta charset="utf-8"><title>Manual Matches</title>';
            $html .= '<style>body{font-family:Arial,Helvetica,sans-serif;padding:20px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ddd;padding:8px;text-align:left}th{background:#f4f4f4}</style>';
            $html .= '</head><body>';
            $html .= '<h1>Manual Matches</h1>';
            $html .= '<p><button onclick="history.back();">Back</button></p>';

            if (empty($rows)) {
                $html .= '<p>No manual matches found.</p>';
            } else {
                $html .= '<table><thead><tr>';
                $html .= '<th>ID</th><th>Batch ID</th><th>Temp ID</th><th>Linked SKU</th><th>Brand Created SKU</th><th>Source Code</th><th>Status</th><th>Created Date</th>';
                $html .= '</tr></thead><tbody>';

                foreach ($rows as $r) {
                    $brandJson = is_string($r['brand_created_sku']) ? $r['brand_created_sku'] : json_encode($r['brand_created_sku']);
                    $html .= '<tr>';
                    $html .= '<td>' . htmlspecialchars($r['id']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['batch_id']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['temp_id']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['linked_sku']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($brandJson) . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['source_code']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['status']) . '</td>';
                    $html .= '<td>' . htmlspecialchars($r['created_date']) . '</td>';
                    $html .= '</tr>';
                }

                $html .= '</tbody></table>';
            }

            $html .= '</body></html>';

            return $this->resultRawFactory->create()->setContents($html);

        } catch (\Exception $e) {
            return $this->resultRawFactory->create()->setContents('<h1>Error</h1><p>' . htmlspecialchars($e->getMessage()) . '</p>');
        }
    }

    protected function _isAllowed()
    {
        return true;
    }
}
