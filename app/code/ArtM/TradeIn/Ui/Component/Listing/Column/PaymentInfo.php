<?php

namespace ArtM\TradeIn\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class PaymentInfo extends Column
{
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
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
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $paymentComment = $item[$fieldName] ?? '';
                
                // Parse existing payment data and populate individual fields
                if (!empty($paymentComment)) {
                    $commentData = json_decode($paymentComment, true);
                    if (is_array($commentData)) {
                        // Set individual field values for inline editing
                        $item['payment_method'] = $commentData['payment_comment'] ?? '';
                        $item['ref_id'] = $commentData['ref_id'] ?? '';
                        $item['amount'] = $commentData['amount'] ?? '';
                    } else {
                        // Handle old string format
                        $item['payment_method'] = $paymentComment;
                        $item['ref_id'] = '';
                        $item['amount'] = '';
                    }
                } else {
                    // Set empty defaults for new records
                    $item['payment_method'] = '';
                    $item['ref_id'] = '';
                    $item['amount'] = '';
                }
            }
        }

        return $dataSource;
    }
}
