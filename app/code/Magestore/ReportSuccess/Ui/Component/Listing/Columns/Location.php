<?php
/**
 *  Copyright © 2018 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Magestore\ReportSuccess\Ui\Component\Listing\Columns;

/**
 * Columns - Location
 */
class Location extends Scroll
{
    /**
     * Prepare component configuration
     *
     * @return void
     */
    public function prepare()
    {
        parent::prepare();
        $data = $this->getData();
        if ($data && $data['config']) {
            if ($this->reportManagement->isMSIEnable()) {
                $data['config']['label'] = __('Source');
                $data['config']['caption'] = __('All Sources');
                $this->setData($data);
            }
        }
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $warehouseNameArray = [];
                if (isset($item[$fieldName])) {
                    $item[$fieldName] = explode(',', (string)$item[$fieldName]);
                    foreach ($item[$fieldName] as $warehouseId) {
                        if ($this->reportManagement->isMSIEnable()) {
                            $sourceModel = $this->objectManager
                                ->create(\Magento\InventoryApi\Api\SourceRepositoryInterface::class)
                                ->get($warehouseId);
                            $warehouseNameArray[] = $sourceModel->getName();
                        }
                    }
                    sort($warehouseNameArray);
                    $item[$fieldName] = $this->addScrollToField(implode('</br>', $warehouseNameArray));
                }

            }
        }
        return $dataSource;
    }
}
