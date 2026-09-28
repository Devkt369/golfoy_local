<?php
/**
 * Copyright [first year code created] Adobe
 * All rights reserved.
 */

namespace ArtM\MsiPriority\Plugin;

use ArtM\MsiPriority\Ui\DataProvider\PosOrder\ListingDataProvider as PosOrderDataProvider;
use Magento\Framework\View\Element\UiComponent\DataProvider\SearchResult;
use ArtM\MsiPriority\Helper\Data;
use Magento\Framework\App\RequestInterface;

class AddAttributesToUiDataProvider
{
    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var RequestInterface
     */
    protected $request;

    public function __construct(Data $helper,RequestInterface $request)
    {
        $this->helper = $helper;
        $this->request = $request;
    }

    /**
     * Apply brand and date filters to the grid collection.
     *
     * @param PosOrderDataProvider $subject
     * @param SearchResult $result
     * @return SearchResult
     */
    public function afterGetSearchResult(PosOrderDataProvider $subject, SearchResult $result)
    {
        // return $result;
        $isMassAction = $this->request->getParam('namespace') && $this->request->getParam('selected');

        if ($isMassAction) {
            return $result;
        }
        // try {
            $startDate = $this->helper->getStartDate(); // e.g., '2025-07-07 06:46:55'
            $brandIds = $this->helper->getSelectedBrands(); // e.g., ['123', '456']
    
            if ($startDate) {
                $result->addFieldToFilter('main_table.created_at', ['gteq' => $startDate]);
            }
            // print_r($brandIds);die;
            if (!empty($brandIds)) {
                $connection = $result->getConnection();
    
                $mainTable = $result->getMainTable();
                $soiTable = $connection->getTableName('sales_order_item');
                $cpeiTable = $connection->getTableName('catalog_product_entity_int');
                $eaTable = $connection->getTableName('eav_attribute');
    
                // Get brand attribute ID dynamically
                $brandAttributeId = $connection->fetchOne(
                    $connection->select()
                        ->from($eaTable, ['attribute_id'])
                        ->where('attribute_code = ?', 'brand')
                        ->where('entity_type_id = ?', 4) // 4 = catalog_product
                );
    
                // Join sales_order_item → product_id
                $result->getSelect()->join(
                    ['soi' => $soiTable],
                    'main_table.entity_id = soi.order_id',
                    []
                );
    
                // Join catalog_product_entity_int to get brand attribute
                $result->getSelect()->join(
                    ['cpei' => $cpeiTable],
                    'soi.product_id = cpei.entity_id AND cpei.attribute_id = ' . (int)$brandAttributeId,
                    []
                );
    
                // Filter by brand value
                $result->getSelect()->where('cpei.value IN (?)', $brandIds);
                $result->getSelect()->group('main_table.entity_id');
            }
            // Add additional filters if needed
            // print_r($result->getSelect()->__toString());die;
            return $result;

        //     }catch (\Exception $e) {
        //     return $result; // Return the result even if there's an error
        // }
    }
}
