<?php

namespace ArtM\EstimateDelivery\Ui\Component\Listing\Column;

use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Ui\Component\Listing\Columns\Column;

class FormattedDate extends Column
{
    protected $timezone;

    public function __construct(
        TimezoneInterface $timezone,
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Framework\View\Element\UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        $this->timezone = $timezone;

        parent::__construct(
            $context,
            $uiComponentFactory,
            $components,
            $data
        );
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {

            foreach ($dataSource['data']['items'] as &$item) {

                $field = $this->getData('name');

                if (!empty($item[$field])) {

                    $date = new \DateTime($item[$field]);

                    $item[$field] = $this->timezone
                        ->formatDateTime(
                            $date,
                            \IntlDateFormatter::LONG,
                            \IntlDateFormatter::NONE
                        );
                }
            }
        }

        return $dataSource;
    }
}