<?php

namespace ArtM\RecommendedSort\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class QueueActions extends Column
{
    const URL_PATH_INITIATE = 'artm_recommendedsort/queueobserver/calculatesingleproduct';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['id']) && isset($item['product_id'])) {
                    $url = $this->urlBuilder->getUrl(
                        self::URL_PATH_INITIATE,
                        [
                            'id' => $item['id'],
                            'product_id' => $item['product_id'],
                        ]
                    );

                    $item[$this->getData('name')] = sprintf(
                        '<a href="%s">Re-Calculate</a>',
                        $url
                    );
                }
            }
        }

        return $dataSource;
    }
}
