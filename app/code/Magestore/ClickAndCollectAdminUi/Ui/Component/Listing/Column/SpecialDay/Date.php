<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\ClickAndCollectAdminUi\Ui\Component\Listing\Column\SpecialDay;

use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;

/**
 * Class to build edit and delete link for each item.
 */
class Date extends Column
{
    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if ($item[$this->getData('name')]) {
                    $date = new \DateTime($item[$this->getData('name')]);
                    if ($item[SpecialDayInterface::REPEAT] == SpecialDayInterface::REPEAT_NONE) {
                        $item[$this->getData('name')] = $date->format('d/m/Y');
                    } else {
                        $item[$this->getData('name')] = $date->format('d/m');
                    }
                }
            }
        }

        return $dataSource;
    }
}
