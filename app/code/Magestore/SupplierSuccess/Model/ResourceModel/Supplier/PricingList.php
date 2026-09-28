<?php
/**
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magestore\SupplierSuccess\Model\ResourceModel\Supplier;

/**
 * Resource pricing list
 */
class PricingList extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('os_supplier_pricinglist', 'supplier_pricinglist_id');
    }

    /**
     * Add pricing list
     *
     * @param array $data
     * @return void
     */
    public function addPricingList($data)
    {
        $connection = $this->getConnection();
        $table = $this->getTable('os_supplier_pricinglist');
        $savingData = $this->modifyPricingListData($data);
        foreach ($savingData as $datum) {
            if (count($datum)) {
                $connection->insertOnDuplicate($table, $datum);
            }
        }
    }

    /**
     * Modify pricing data to remove field date if it is nullable
     *
     * @param array $data
     * @return array
     */
    public function modifyPricingListData($data)
    {
        $savingData = [
            'full_date' => [],
            'no_date' => [],
            'no_start_date' => [],
            'no_end_date' => []
        ];
        foreach ($data as $datum) {
            $type = 'full_date';
            if ($datum['start_date'] == "") {
                unset($datum['start_date']);
                $type = 'no_start_date';
            }
            if ($datum['end_date'] == "") {
                unset($datum['end_date']);
                if ($type == 'no_start_date') {
                    $type = 'no_date';
                } else {
                    $type = 'no_end_date';
                }
            }
            $savingData[$type][] = $datum;
        }
        return $savingData;
    }
}
