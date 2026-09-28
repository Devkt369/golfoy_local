<?php
/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\RewardpointsGraphQl\Model\Transaction;

use Magento\Framework\GraphQl\ConfigInterface;
use Magento\Framework\GraphQl\Query\Resolver\Argument\FieldEntityAttributesInterface;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magestore\RewardpointsGraphQl\Model\RewardPointTransactionTypeResolver;
use Magento\CatalogGraphQl\Model\Resolver\Products\SearchResult;

/**
 * Retrieve filterable attributes for msGetRewardPointTransactionHistory queries
 */
class TransactionFilterAttributesForAst implements FieldEntityAttributesInterface
{
    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(
        ConfigInterface $config
    ) {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getEntityAttributes(): array
    {
        $fields = [];
        /** @var Field $field */
        foreach ($this->config->getConfigElement(
            RewardPointTransactionTypeResolver::REWARD_TRANSACTION_TYPE_RESOLVER
        )->getFields() as $field) {
            $fields[$field->getName()] = [
                'type' => 'String',
                'fieldName' => $field->getName(),
            ];
        }
        // Sign of logic change in different Magento version
        if (method_exists(
            SearchResult::class,
            'getPageSize'
        )) {
            return $fields;
        } else {
            return array_keys($fields);
        }
    }
}
