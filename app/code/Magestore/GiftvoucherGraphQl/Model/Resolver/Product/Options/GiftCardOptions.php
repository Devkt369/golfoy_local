<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver\Product\Options;

use Magento\Catalog\Api\Data\ProductCustomOptionInterface;
use Magento\Catalog\Api\Data\ProductCustomOptionInterfaceFactory;
use Magento\Catalog\Model\Product\Option;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Catalog\Model\Product;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magestore\Giftvoucher\Model\Giftcard\Option as GiftCardOption;
use Magestore\Giftvoucher\Model\Source\GiftCardTypeOptions;
use Magestore\Giftvoucher\Model\Source\GiftType;

/**
 * Resolver for the gift card options on the Gift Card Product
 */
class GiftCardOptions implements ResolverInterface
{
    /**
     * Option type name
     */
    private const OPTION_TYPE = 'giftcard';

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

        /** @var Product $product */
        $product = $value['model'];

        return $this->getCustomOptionsData($product);
    }

    /**
     * Format custom options data
     *
     * @param Product $product
     * @return array
     */
    private function getCustomOptionsData(Product $product): array
    {
        $customOptions = [];
        if ($product->getGiftCardType() != GiftCardTypeOptions::TYPE_PHYSICAL) {
            $recipientOptions = $this->getEmailOptions();
            $giftMessageOption = $this->getGiftMessageOptions();
            $giftTemplateOption = $this->getGiftCardTemplate();
            $customOptions = array_merge($customOptions, $recipientOptions, $giftMessageOption, $giftTemplateOption);
        }
        if ($product->getGiftType() != GiftType::GIFT_TYPE_FIX) {
            $customAmountOption = $this->getCustomAmountOptions();
            $customOptions = array_merge($customOptions, $customAmountOption);
        }
        $customOptions = array_merge($customOptions, $this->getSendFriend());
        $customOptions = array_merge($customOptions, $this->getRecipientAddress());
        return $customOptions;
    }

    /**
     * Get Recipient Options data
     *
     * @return array|array[]
     */
    private function getEmailOptions(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Sender Name'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_CUSTOMER_NAME,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_CUSTOMER_NAME
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Recipient Name'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_RECIPIENT_NAME,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_RECIPIENT_NAME
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Recipient Email'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_RECIPIENT_EMAIL,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_RECIPIENT_EMAIL
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Day to send'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_DAY_TO_SEND,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_DAY_TO_SEND
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Timezone to send'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_TIMEZONE_TO_SEND,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_TIMEZONE_TO_SEND
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Notify Success'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_NOTIFY_SUCCESS,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_NOTIFY_SUCCESS
                    ]))
                ]
            ],
        ];
    }

    /**
     * Get recipient address
     *
     * @return array|array[]
     */
    private function getRecipientAddress(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Recipient Address'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_RECIPIENT_ADDRESS,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_RECIPIENT_ADDRESS
                    ]))
                ]
            ]
        ];
    }

    /**
     * Get send friend data
     *
     * @return array|array[]
     */
    private function getSendFriend(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Send Friend'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_SEND_FRIEND,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_SEND_FRIEND
                    ]))
                ]
            ]
        ];
    }

    /**
     * Get Gift Message Options data
     *
     * @return array|array[]
     */
    private function getGiftMessageOptions(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Message'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_MESSAGE,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_MESSAGE
                    ]))
                ]
            ]
        ];
    }

    /**
     * Get Gift card template id
     *
     * @return array|array[]
     */
    private function getGiftCardTemplate(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Gift Card Template Id'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_GIFTCARD_TEMPLATE_ID,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_GIFTCARD_TEMPLATE_ID
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Gift Card Template Image'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_GIFTCARD_TEMPLATE_IMAGE,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_GIFTCARD_TEMPLATE_IMAGE
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Gift Card Template Custom Image'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 0,
                'value' => [
                    'option_id' => GiftCardOption::KEY_GIFTCARD_CUSTOM_IMAGE,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_GIFTCARD_CUSTOM_IMAGE
                    ]))
                ]
            ]
        ];
    }

    /**
     * Get Custom Amount Options data
     *
     * @return array|array[]
     */
    private function getCustomAmountOptions(): array
    {
        return [
            [
                Option::KEY_TITLE => __('Custom Gift Card Amount'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_GIFTCARD_AMOUNT,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_GIFTCARD_AMOUNT
                    ]))
                ]
            ],
            [
                Option::KEY_TITLE => __('Amount'),
                Option::KEY_TYPE => ProductCustomOptionInterface::OPTION_TYPE_FIELD,
                'required' => 1,
                'value' => [
                    'option_id' => GiftCardOption::KEY_AMOUNT,
                    'uid' => base64_encode(implode('/', [
                        self::OPTION_TYPE,
                        GiftCardOption::KEY_AMOUNT
                    ]))
                ]
            ]
        ];
    }
}
