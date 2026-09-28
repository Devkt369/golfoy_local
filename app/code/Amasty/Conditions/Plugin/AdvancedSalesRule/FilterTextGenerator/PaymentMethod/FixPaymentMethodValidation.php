<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Advanced Conditions for Magento 2
 */

namespace Amasty\Conditions\Plugin\AdvancedSalesRule\FilterTextGenerator\PaymentMethod;

use Magento\AdvancedSalesRule\Model\Rule\Condition\FilterTextGenerator\Address\PaymentMethod;
use Magento\Framework\DataObject;

/**
 * Copy payment method to shipping address for correct validation.
 *
 * Payment method is not stored in the address table in DB.
 * So it should be set manually before address validation.
 * Magento does set it in old validation (not AdvancedRules). This fix does the same.
 * @see \Magento\SalesRule\Model\Rule\Condition\Address::validate
 */
class FixPaymentMethodValidation
{
    private const KEY = 'payment_method';

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @phpstan-ignore-next-line
     */
    public function beforeGenerateFilterText(PaymentMethod $subject, DataObject $quoteAddress): array
    {
        if ($quoteAddress instanceof \Magento\Quote\Model\Quote\Address && !$quoteAddress->getData(self::KEY)) {
            $quote = $quoteAddress->getQuote();
            if ($quote->getId() || $quote->currentPaymentWasSet()) {
                $quoteAddress->setData(self::KEY, $quote->getPayment()->getMethod());
            }
        }

        return [$quoteAddress];
    }
}
