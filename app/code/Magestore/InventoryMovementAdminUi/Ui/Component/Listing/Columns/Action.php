<?php
/**
 *  Copyright © 2020 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\InventoryMovementAdminUi\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\InventoryMovementApi\Api\Data\OrderPotentialErrorInterface;
use Magestore\InventoryMovementApi\Model\OrderTypes;

/**
 * Column note
 */
class Action extends Column
{
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var OrderTypes
     */
    protected $orderTypes;

    /**
     * Action constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param OrderTypes $orderTypes
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        OrderTypes $orderTypes,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
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
                $viewUrlPath = '#';
                $urlEntityParamName = 'entity_id';
                if (isset($item['type_value'])) {
                    $type = $item['type_value'];
                    $orderTypesData = $this->orderTypes->getData();
                    if (isset($orderTypesData[$type])
                        && isset($orderTypesData[$type]['viewUrlPath'])
                        && isset($orderTypesData[$type]['urlEntityParamName'])
                    ) {
                        $viewUrlPath = $orderTypesData[$type]['viewUrlPath'];
                        $urlEntityParamName = $orderTypesData[$type]['urlEntityParamName'];
                    }
                }
                $item[$name] = [
                    'view' => [
                        'href' => $this->urlBuilder->getUrl(
                            $viewUrlPath,
                            [
                                $urlEntityParamName => $item[OrderPotentialErrorInterface::ORDER_ID]
                            ]
                        ),
                        'label' => __('View')
                    ]
                ];
            }
        }
        return $dataSource;
    }
}
