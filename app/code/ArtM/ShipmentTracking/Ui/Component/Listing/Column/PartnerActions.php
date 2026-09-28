<?php

declare(strict_types=1);

namespace ArtM\ShipmentTracking\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PartnerActions extends Column
{
    private const URL_PATH_EDIT = 'artm_shipmenttracking/partner/edit';
    private const URL_PATH_DELETE = 'artm_shipmenttracking/partner/delete';

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        private readonly UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as &$item) {
            if (!isset($item['partner_id'])) {
                continue;
            }

            $name = $this->getData('name');
            $item[$name]['edit'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['partner_id' => $item['partner_id']]),
                'label' => __('Edit'),
            ];
            $item[$name]['delete'] = [
                'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['partner_id' => $item['partner_id']]),
                'label' => __('Delete'),
                'confirm' => [
                    'title' => __('Delete partner'),
                    'message' => __('Are you sure you want to delete this partner?'),
                ],
                'post' => true,
            ];
        }

        return $dataSource;
    }
}
