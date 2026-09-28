<?php

namespace ArtM\RecommendedSort\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class LogFileDownload extends Column
{
    protected $urlBuilder;

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
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $filename = $item['output_log'] ?? '';
                $id = $item['id'] ?? '';

                if ($filename) {
                    $fileUrl = $this->urlBuilder->getUrl(
                        'artm_recommendedsort/log/download',
                        [
                            'file' => $filename,
                            'id' => $id
                        ]
                    );
                    $item['output_log'] = '<a href="' . $fileUrl . '" target="_blank" download>Download</a>';
                } else {
                    $item['output_log'] = '<em>No File</em>';
                }
            }
        }

        return $dataSource;

    }
}
