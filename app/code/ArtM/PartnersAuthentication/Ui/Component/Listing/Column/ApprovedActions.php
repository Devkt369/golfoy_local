<?php

namespace ArtM\PartnersAuthentication\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class ApprovedActions extends Column
{
    protected $urlBuilder;
    protected $HelloWorldFactory;

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

                    // Check if has_account is NOT true/1
                    if (empty($item['has_account']) || $item['has_account'] == false || $item['has_account'] == '0') {
                        $item[$this->getData('name')] = [
                            [
                                'href' => $this->urlBuilder->getUrl(
                                    'artm_partnersauthentication/dealer/approve',
                                    [
                                        'data' => $item['id']."||".$item['email']."||".$item['name']."||".$item['telephone']."||".$item['query_type']
                                    ]
                                ),
                                'label' => __('Approve'),
                                'confirm' => [
                                    'title' => __('Approve'),
                                    'message' => __('Are you sure you want to approve this account?')
                                ]
                            ],
                            [
                                'href' => $this->urlBuilder->getUrl(
                                    'artm_partnersauthentication/dealer/deleterequest',
                                    [
                                        'id' => $item['id']
                                    ]
                                ),
                                'label' => __('Remove Request'),
                                'confirm' => [
                                    'title' => __('Remove Request'),
                                    'message' => __('Are you sure you want to remove this request?')
                                ]
                            ]
                        ];
                    } else {
                        $item[$this->getData('name')] = [
                            [
                                'href' => $this->urlBuilder->getUrl(
                                    'artm_partnersauthentication/dealer/deleterequest',
                                    [
                                        'id' => $item['id']
                                    ]
                                ),
                                'label' => __('Remove Request'),
                                'confirm' => [
                                    'title' => __('Remove Request'),
                                    'message' => __('Are you sure you want to remove this request?')
                                ]
                            ]
                        ];
                    }
                }
            }
        }
        return $dataSource;
    }
}
