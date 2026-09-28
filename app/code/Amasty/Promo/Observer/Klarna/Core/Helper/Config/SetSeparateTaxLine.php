<?php

declare(strict_types=1);

/**
 * @author Amasty Team
 * @copyright Copyright (c) Amasty (https://www.amasty.com)
 * @package Free Gift Base for Magento 2
 */

namespace Amasty\Promo\Observer\Klarna\Core\Helper\Config;

use Klarna\Base\Api\VersionInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Tax\Model\Config as TaxConfig;

/**
 * Set separate tax line option for klarna totals equality
 * \Klarna\Core\Model\Checkout\Orderline\Items::153
 *
 * event name kp_load_version_details
 */
class SetSeparateTaxLine implements ObserverInterface
{
    /**
     * @var TaxConfig
     */
    protected $taxConfig;

    public function __construct(
        TaxConfig $taxConfig
    ) {
        $this->taxConfig = $taxConfig;
    }

    public function execute(Observer $observer): void
    {
        /** @var VersionInterface $optionsObject */
        $optionsObject = $observer->getData('options');
        if (!$this->taxConfig->priceIncludesTax()) {
            if (method_exists($optionsObject, 'setSeparateTaxLine')) {
                $optionsObject->setSeparateTaxLine(true);
            }
        }
    }
}
