<?php

namespace ArtM\BulkOrder\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\UrlInterface;

class DownloadCsv extends Column
{
    const URL_PATH_DOWNLOAD = 'artm_bulkorder/partnersbulkcsv/downloadcsv';
    const URL_PATH_VIEW = 'artm_bulkorder/partnersbulkcsv/viewcsv';

    /**
     * @var UrlInterface
     */
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

    /**
     * Add actions for each row
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['order_file']) && $item['order_file']) {
                    $fileName = $item['order_file'];
                    $id = $item['id'];

                    $item[$this->getData('name')] = [
                        'download' => [
                            'href' => $this->urlBuilder->getUrl(
                                self::URL_PATH_DOWNLOAD,
                                ['file' => $fileName, 'id' => $id]
                            ),
                            'label' => __('Download'),
                            'hidden' => false,
                        ],
                        'view' => [
                            'href' => $this->urlBuilder->getUrl(
                                self::URL_PATH_VIEW,
                                ['file' => $fileName, 'id' => $id]
                            ),
                            'label' => __('View'),
                            'target' => '_blank',
                            'hidden' => false,
                        ],
                    ];
                } else {
                    $item[$this->getData('name')] = [
                        'nofile' => [
                            'label' => __('No File'),
                            'hidden' => true
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
