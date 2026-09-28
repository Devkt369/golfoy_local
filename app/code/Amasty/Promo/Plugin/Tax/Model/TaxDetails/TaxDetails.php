<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Plugin\Tax\Model\TaxDetails;

use Amasty\Promo\Model\Config;
use Amasty\Promo\Model\Config\Source\GiftRepresentationMode;
use Amasty\Promo\Model\Storage;
use Magento\Tax\Model\TaxDetails\TaxDetails as TaxDetailsModel;

class TaxDetails
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * @param TaxDetailsModel $subject
     * @param TaxDetailsModel $result
     * @return mixed
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterSetItems(TaxDetailsModel $subject, $result)
    {
        if (isset($result->getData()['items'])) {
            foreach ($result->getData()['items'] as $key => &$value) {
                if (array_key_exists($key, Storage::$cachedFreeGiftsWithTax)) {
                    if ($this->config->getGiftRepresentationMode() !== GiftRepresentationMode::SHOW_FULL_PRICE) {
                        $value->setPriceInclTax($value->getPriceInclTax() - $value->getPrice());
                        $value->setPrice(0);
                    }

                    $result->setSubtotal($result->getSubtotal() - $value->getRowTotal());

                    if ($this->config->getGiftRepresentationMode() !== GiftRepresentationMode::SHOW_FULL_PRICE) {
                        $value->setRowTotalInclTax($value->getRowTotalInclTax() - $value->getRowTotal());
                        $value->setRowTotal(0);
                    } else {
                        $value->setRowTax($value->getRowTotalInclTax() - $value->getRowTotal());
                    }

                    Storage::$cachedQuoteItemPricesWithTax[Storage::$cachedFreeGiftsWithTax[$key]]['price_incl_tax']
                        = $value->getPriceInclTax();
                    Storage::$cachedQuoteItemPricesWithTax[Storage::$cachedFreeGiftsWithTax[$key]]['row_total_incl_tax']
                        = $value->getRowTotalInclTax();
                }
            }
        }

        return $result;
    }
}
