<?php
declare(strict_types=1);

namespace ArtM\ProductSlider\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class SliderActions extends Column
{
    private const URL_PATH_EDIT = 'artm_productslider/slider/edit';
    private const URL_PATH_DELETE = 'artm_productslider/slider/delete';
    private const URL_PATH_DUPLICATE = 'artm_productslider/slider/duplicate';

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
            if (!isset($item['slider_id'])) {
                continue;
            }

            $name = $this->getData('name');
            $item[$name] = [
                'edit' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_EDIT, ['slider_id' => $item['slider_id']]),
                    'label' => __('Edit'),
                ],
                'duplicate' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DUPLICATE, ['slider_id' => $item['slider_id']]),
                    'label' => __('Duplicate'),
                ],
                'delete' => [
                    'href' => $this->urlBuilder->getUrl(self::URL_PATH_DELETE, ['slider_id' => $item['slider_id']]),
                    'label' => __('Delete'),
                    'confirm' => [
                        'title' => __('Delete "%1"', $item['title'] ?? ''),
                        'message' => __('Are you sure you want to delete this slider?'),
                    ],
                    'post' => true,
                ],
            ];
        }

        return $dataSource;
    }
}
