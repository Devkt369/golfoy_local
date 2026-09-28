<?php
namespace ArtM\MassProducts\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class DraftActions extends Column
{
    private $urlBuilder;

    public function __construct(
        UrlInterface $urlBuilder,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as & $item) {
            if (!isset($item['entity_id'])) {
                continue;
            }

            $item[$this->getData('name')]['edit'] = [
                'href' => $this->urlBuilder->getUrl('massproducts/draft/edit', ['id' => $item['entity_id']]),
                'label' => __('Edit'),
            ];
        }

        return $dataSource;
    }
}
