<?php

namespace ArtM\MsiPriority\Ui\Component\PosOrder\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    const SYNC_TO_VIN = 'msipriority/order/synctovinculum';

    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $HelloWorldFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $HelloWorldFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {   
       
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['entity_id'])) {
                    $item[$this->getData('name')] = [
                        [
                            'href' => $this->urlBuilder->getUrl(
                                'sales/order/view',
                                ['order_id' => $item['entity_id']]
                            ),
                            'label' => __('View Order'),
                            'target' => '_blank'
                        ],
                        [
                            'href' => $this->urlBuilder->getUrl(
                                'msipriority/order/synctovinculum',
                                ['entity_id' => $item['entity_id']]
                            ),
                            'label' => __('Sync'),
                            'confirm' => [
                                'title' => __('Sync'),
                                'message' => __('Are you sure you want to sync this order to Vinculum?')
                            ]
                        ],
                        [
                            'href' => $this->urlBuilder->getUrl(
                                'msipriority/archiveorder/addtoarchive',
                                [   
                                    'entity_id' => $item['entity_id'],
                                    'status' => $item['status'],
                                    'created_at' => $item['created_at'],
                                    'increment_id' => $item['increment_id'],
                                ]
                            ),
                            'label' => __('Archive'),
                            'confirm' => [
                                'title' => __('Archive'),
                                'message' => __('Are you sure you want to archive this order?')
                            ]
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
