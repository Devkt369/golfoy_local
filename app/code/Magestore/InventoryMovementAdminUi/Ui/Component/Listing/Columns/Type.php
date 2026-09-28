<?php
/**
 *  Copyright © 2020 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Ui\Component\Listing\Columns;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\InventoryMovementApi\Model\OrderTypes;

/**
 * Column type for Order Potential Error
 */
class Type extends Column
{
    /**
     * @var OrderTypes
     */
    protected $orderTypes;

    /**
     * Type constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param OrderTypes $orderTypes
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        OrderTypes $orderTypes,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->orderTypes = $orderTypes;
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
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item[$name])) {
                    $orderTypesData = $this->orderTypes->getData();
                    $value = $item[$name];
                    if (isset($orderTypesData[$value])
                        && isset($orderTypesData[$value]['label'])
                    ) {
                        $item[$name] = $orderTypesData[$value]['label'];
                    }
                    $item['type_value'] = $value;
                }
            }
        }
        return $dataSource;
    }
}
