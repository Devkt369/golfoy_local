<?php

/**
 * Copyright © 2018 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magestore\Webpos\Model\ResourceModel\Directory;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

/**
 * Class CurrencyRates
 *
 * Get currency rates
 */
class CurrencyRates
{
    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
    }

    /**
     * Get currency rates
     *
     * @param string $fromCode
     * @param array $toCodes
     * @return array
     */
    public function getCurrencyRates(string $fromCode, array $toCodes)
    {
        $select = $this->connection->select()->from(
            [
                'currency_rate' => $this->resource->getTableName('directory_currency_rate'),
            ]
        )->where('currency_from = ?', $fromCode)
        ->where('currency_to IN (?)', $toCodes);
        $data = $this->connection->fetchAll($select);
        $rates = [];
        if (!empty($data)) {
            foreach ($data as $rate) {
                $rates[$rate['currency_to']] = $rate['rate'];
            }
        }
        return $rates;
    }
}
