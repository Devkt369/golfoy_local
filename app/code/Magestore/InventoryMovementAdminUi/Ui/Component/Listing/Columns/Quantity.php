<?php
/**
 *  Copyright © 2020 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Ui\Component\Listing\Columns;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Column quantity
 */
class Quantity extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if ($item[$name]) {
                    $item[$name] = (float) $item[$name];
                }
            }
        }

        return $dataSource;
    }
}
