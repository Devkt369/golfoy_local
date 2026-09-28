<?php

namespace ArtM\InvoiceGrid\Ui\Component\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\App\Filesystem\DirectoryList;

class Actions extends Column
{

    const URL_DOWNLOAD = 'invoicegrid/export/fcsv';

    protected $urlBuilder;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $HelloWorldFactory,
        UrlInterface $urlBuilder,
        DirectoryList $directoryList,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->directoryList = $directoryList;
        parent::__construct($context, $HelloWorldFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource)
    {   
       
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if (isset($item['id'])) {
                    $filenamestr = $item['invoice'];
                    $filename    = explode(", ", $filenamestr);
                    $item[$this->getData('name')] = [
                        'downloadinvoice' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_DOWNLOAD,
                                [
                                    'id' => $filename[0],
                                ]
                                ),
                            'label' => __('Download Invoice Csv'),
                        ],
                        'downloadstock' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_DOWNLOAD,
                                [
                                    'id' => $filename[1],
                                ]
                                ),
                            'label' => __('Download Stock Csv'),
                        ],
                    ];
                }
            }
        }

        return $dataSource;
    }
}
