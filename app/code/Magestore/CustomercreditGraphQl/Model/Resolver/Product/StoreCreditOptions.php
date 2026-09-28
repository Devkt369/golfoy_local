<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\CustomercreditGraphQl\Model\Resolver\Product;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;

/**
 * Resolver for the store credit
 */
class StoreCreditOptions implements ResolverInterface
{
    /**
     * Option type name
     */
    private const OPTION_TYPE = 'customercredit';

    /**
     * @var ProductCustomOptionInterfaceFactory
     */
    private $customOptionFactory;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ProductCustomOptionInterfaceFactory $customOptionFactory
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ProductCustomOptionInterfaceFactory $customOptionFactory,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->customOptionFactory = $customOptionFactory;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field $field,
        $context,
        ResolveInfo $info,
        array $value = null,
        array $args = null
    ) {
        if (!isset($value['model'])) {
            throw new GraphQlInputException(__('"model" value should be specified'));
        }

        return $this->getCustomOptionsData();
    }

    /**
     * Format custom options data
     *
     * @return array
     */
    private function getCustomOptionsData(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Credit Price Amount'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => 'credit_price_amount',
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        'credit_price_amount'
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Credit Amount'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => 'amount',
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        'amount'
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Customer Name'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => 'customer_name',
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        'customer_name'
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Send Friend'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => 'send_friend',
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        'send_friend'
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Recipient name'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => 'recipient_name',
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        'recipient_name'
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Recipient email'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => 'recipient_email',
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        'recipient_email'
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Custom message'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => 'message',
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        'message'
                    ]))
                ]
            ]
        ];
    }
}
