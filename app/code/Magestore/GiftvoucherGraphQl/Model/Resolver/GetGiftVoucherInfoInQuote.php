<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magestore\GiftvoucherGraphQl\Model\Resolver;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Quote\Model\Quote;
use Magestore\Giftvoucher\Helper\Data as HelperData;

/**
 * Get reward point info for quote
 */
class GetGiftVoucherInfoInQuote implements ResolverInterface
{
    const XML_ENABLE = 'active';

    /**
     * @var HelperData
     */
    private $helperData;

    /**
     * @var Json
     */
    private $json;

    /**
     * Constructor
     *
     * @param HelperData $helperData
     * @param Json $json
     */
    public function __construct(
        HelperData $helperData,
        Json $json
    ) {
        $this->helperData = $helperData;
        $this->json = $json;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        $currentStore = $context->getExtensionAttributes()->getStore();

        if (!(int) $this->helperData->getGeneralConfig(self::XML_ENABLE, $currentStore->getId())) {
            return 0;
        }

        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var Quote $quote */
        $quote = $value['model'];

        $giftVoucherGiftCodes = [];
        if ($quote->getData('gift_voucher_gift_codes')) {
            $giftVoucherGiftCodes = explode(',', $quote->getData('gift_voucher_gift_codes'));
        }

        $giftCodesAppliedDiscount = [];
        $giftCodesAppliedDiscountForShipping = [];

        if ($quote->getData('giftcodes_applied_discount_for_shipping')) {
            $giftCodesAppliedDiscountForShipping = $this->json->unserialize(
                $quote->getData('giftcodes_applied_discount_for_shipping')
            );
        }

        $codesDiscountArray = [];
        if ($quote->getData('codes_discount')) {
            $codesDiscountArray = explode(',', $quote->getData('codes_discount'));
        }

        $codesBaseDiscountArray = [];
        if ($quote->getData('codes_base_discount')) {
            $codesBaseDiscountArray = explode(',', $quote->getData('codes_base_discount'));
        }

        foreach ($giftVoucherGiftCodes as $key => $code) {
            $giftCodesAppliedDiscount[] = [
                'code' => $code,
                'discount' =>
                    (isset($codesDiscountArray[$key])) ? (float) $codesDiscountArray[$key] : 0,
                'base_discount' =>
                    (isset($codesBaseDiscountArray[$key])) ? (float) $codesBaseDiscountArray[$key] : 0
            ];
        }

        return [
            'giftvoucher_base_shipping_hidden_tax_amount'
                => (float) $quote->getData('giftvoucher_base_shipping_hidden_tax_amount'),
            'giftvoucher_shipping_hidden_tax_amount'
                => (float) $quote->getData('giftvoucher_shipping_hidden_tax_amount'),
            'base_giftvoucher_discount_for_shipping'
                => (float) $quote->getData('base_giftvoucher_discount_for_shipping'),
            'giftvoucher_discount_for_shipping' => (float) $quote->getData('giftvoucher_discount_for_shipping'),
            'giftvoucher_base_hidden_tax_amount' => (float) $quote->getData('giftvoucher_base_hidden_tax_amount'),
            'giftvoucher_hidden_tax_amount' => (float) $quote->getData('giftvoucher_hidden_tax_amount'),
            'base_gift_voucher_discount' => (float) $quote->getData('base_gift_voucher_discount'),
            'gift_voucher_discount' => (float) $quote->getData('gift_voucher_discount'),
            'gift_voucher_gift_codes' => $giftVoucherGiftCodes,
            'giftcodes_applied_discount' => $giftCodesAppliedDiscount,
            'giftcodes_applied_discount_for_shipping' => $giftCodesAppliedDiscountForShipping,
        ];
    }
}
