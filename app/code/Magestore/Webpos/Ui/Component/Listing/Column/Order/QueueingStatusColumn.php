<?php
/**
 *  Copyright © 2020 Magestore. All rights reserved.
 *  See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\Webpos\Ui\Component\Listing\Column\Order;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Magestore\Webpos\Api\Data\Checkout\PosOrderInterface;
use Magestore\Webpos\Model\Source\Order\QueueingStatus as StatusSource;

/**
 * Column queueing status
 */
class QueueingStatusColumn extends Column
{
    /**
     * @var StatusSource
     */
    protected $statusSource;

    /**
     * Status constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param StatusSource $statusSource
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        StatusSource $statusSource,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->statusSource = $statusSource;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                $value = $item[$name];
                $label = $this->statusSource->getOptionText($value);
                switch ($value) {
                    case PosOrderInterface::QUEUEING_STATUS_PROCESSING:
                        $item[$name] = '<span class="grid-severity-notice grid-severity-processing"><span>'
                            . $label . '</span></span>';
                        break;
                    case PosOrderInterface::QUEUEING_STATUS_FAIL:
                        $item[$name] = '<span class="grid-severity-critical"><span>' . $label . '</span></span>';
                        break;
                }
            }
        }

        return $dataSource;
    }
}
