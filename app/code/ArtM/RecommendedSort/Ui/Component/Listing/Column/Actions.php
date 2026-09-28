<?php

namespace ArtM\RecommendedSort\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    const URL_PATH_INITIATE = 'artm_recommendedsort/index/initiate';

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
                if (isset($item['id'])) {
                    if (isset($item['status']) && !in_array($item['status'], ['initiated','Initiated', 'pending'])) {
                        $item[$this->getData('name')] = sprintf(
                            '<a href="%s">%s</a>',
                            $this->urlBuilder->getUrl(self::URL_PATH_INITIATE, ['id' => $item['id']]),
                            __('Initiate')
                        );
                    } else {
                        $item[$this->getData('name')] = '';
                    }
                }
            }
        }

        return $dataSource;
    }
}
