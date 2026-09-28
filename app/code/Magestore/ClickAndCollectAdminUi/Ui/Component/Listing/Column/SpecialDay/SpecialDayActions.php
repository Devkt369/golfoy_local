<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\ClickAndCollectAdminUi\Ui\Component\Listing\Column\SpecialDay;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\ClickAndCollectApi\Api\Data\SpecialDayInterface;

/**
 * Class to build edit and delete link for each item.
 */
class SpecialDayActions extends Column
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'clickandcollectadmin/specialday/edit';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * ScheduleActions constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
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

    /**
     * @inheritDoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as & $item) {
                if (isset($item[SpecialDayInterface::SPECIAL_DAY_ID])) {
                    $item[$this->getData('name')] = [
                        'edit' => [
                            'href' => $this->urlBuilder->getUrl(
                                static::URL_PATH_EDIT,
                                [
                                    SpecialDayInterface::SPECIAL_DAY_ID =>
                                        $item[SpecialDayInterface::SPECIAL_DAY_ID],
                                ]
                            ),
                            'label' => __('Edit'),
                        ]
                    ];
                }
            }
        }

        return $dataSource;
    }
}
