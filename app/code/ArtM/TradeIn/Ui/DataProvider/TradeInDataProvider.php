<?php

namespace ArtM\TradeIn\Ui\DataProvider;

use ArtM\TradeIn\Model\ResourceModel\TradeIn\CollectionFactory;
use Magento\Ui\DataProvider\AbstractDataProvider;

class TradeInDataProvider extends AbstractDataProvider
{
    protected $collection;
    protected $addFieldStrategies;
    protected $addFilterStrategies;

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $addFieldStrategies
     * @param array $addFilterStrategies
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $addFieldStrategies = [],
        array $addFilterStrategies = [],
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->addFieldStrategies = $addFieldStrategies;
        $this->addFilterStrategies = $addFilterStrategies;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (!$this->getCollection()->isLoaded()) {
            $this->getCollection()->load();
        }
        
        $items = $this->getCollection()->toArray();
        $processedItems = [];
        
        foreach ($items['items'] ?? [] as $item) {
            // Parse payment comment to populate individual fields
            $paymentComment = $item['payment_comment'] ?? '';
            if (!empty($paymentComment)) {
                $commentData = json_decode($paymentComment, true);
                if (is_array($commentData)) {
                    $item['payment_method'] = $commentData['payment_comment'] ?? '';
                    $item['ref_id'] = $commentData['ref_id'] ?? '';
                    $item['amount'] = $commentData['amount'] ?? '';
                } else {
                    $item['payment_method'] = $paymentComment;
                    $item['ref_id'] = '';
                    $item['amount'] = '';
                }
            } else {
                $item['payment_method'] = '';
                $item['ref_id'] = '';
                $item['amount'] = '';
            }
            $processedItems[] = $item;
        }
        
        return [
            'totalRecords' => $this->getCollection()->getSize(),
            'items' => $processedItems
        ];
    }
}
