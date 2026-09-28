<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class TrackingStatusActions extends Column
{
    private const URL_PATH_EDIT   = 'ArtM_ShipmentTracking/trackingstatus/edit';
    private const URL_PATH_DELETE = 'ArtM_ShipmentTracking/trackingstatus/delete';

    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['mapping_id'])) {
                    // $item[$name]['edit'] = [
                    //     'href'  => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['mapping_id' => $item['mapping_id']]),
                    //     'label' => __('Edit'),
                    // ];
                    $item[$name]['delete'] = [
                        'href'    => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['mapping_id' => $item['mapping_id']]),
                        'label'   => __('Delete'),
                        'confirm' => [
                            'title'   => __('Delete mapping'),
                            'message' => __('Are you sure you want to delete this mapping?'),
                        ],
                        'post' => true,
                    ];
                }
            }
        }

        return $dataSource;
    }
}
