<?php
/**
 * Copyright © 2020 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\BranchRequest\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class BranchRequest BranchRequestActions
 *
 * Used for BranchRequest actions
 */
class BranchRequestActions extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        $dataSource = parent::prepareDataSource($dataSource);

        if (empty($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            $item[$this->getData('name')]['view'] = [
                'href' => $this->context->getUrl(
                    'branchrequest/branchrequest/edit',
                    ['id' => $item['id']]
                ),
                'label' => __('View'),
                'hidden' => false,
            ];
        }

        return $dataSource;
    }
}
