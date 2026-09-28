<?php
/**
 * Class ProductOrder
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Ui\Component\Form;

/**
 * Class ProductOrder
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class ProductOrder implements \Magento\Framework\Data\OptionSourceInterface
{
    const BESTSELLER = 0;
    const LOW_TO_HIGH_PRICE = 1;
    const HIGH_TO_LOW_PRICE = 2;
    const NEW_ARRIVALS = 3;

    /**
     * Return options for product sort order
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::BESTSELLER, 'label' => __('BestSeller')],
            ['value' => self::LOW_TO_HIGH_PRICE, 'label' => __('Low To High Price')],
            ['value' => self::HIGH_TO_LOW_PRICE, 'label' => __('High To Low Price')],
            ['value' => self::NEW_ARRIVALS, 'label' => __('New Arrivals')],
        ];
    }
}
