<?php

namespace ArtM\MsiPriority\Ui\Component\PosOrder\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ArchiveListActions extends Column
{
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
                if (isset($item['id'])) {
                    $item[$this->getData('name')] = [
                        [
                            'href' => $this->urlBuilder->getUrl(
                                'sales/order/view',
                                ['order_id' => $item['order_id']]
                            ),
                            'label' => __('View Order'),
                            'target' => '_blank'
                        ],
                        [
                            'href' => $this->urlBuilder->getUrl(
                                'msipriority/order/synctovinculum',
                                [
                                    'entity_id' => $item['order_id'],
                                    'from_archive' => 1 // <-- this is the key to pass to your controller
                                ]
                            ),
                            'label' => __('Sync'),
                            'confirm' => [
                                'title' => __('Sync'),
                                'message' => __('Are you sure you want to sync this order to Vinculum?')
                            ]
                        ],
                        [
                            'href' => $this->urlBuilder->getUrl(
                                'msipriority/archiveorder/removetoarchive',
                                [
                                    'id' => $item['id'] // <-- match this key with what your controller expects
                                ]
                            ),
                            'label' => __('Remove'),
                            'confirm' => [
                                'title' => __('Remove From Archive'),
                                'message' => __('Are you sure you want to remove this order from archive?')
                            ]
                        ]
                    ];
                }
            }
        }


        return $dataSource;
    }
}
