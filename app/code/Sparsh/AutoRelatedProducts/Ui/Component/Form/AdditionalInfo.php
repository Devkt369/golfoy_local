<?php
/**
 * Class AdditionalInfo
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
 * Class AdditionalInfo
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class AdditionalInfo implements \Magento\Framework\Data\OptionSourceInterface
{
    const PRICE = 1;
    const ADD_TO_CART = 2;
    const ADD_TO_WISHLIST = 3;
    const ADD_TO_COMPARE = 4;
    const REVIEW = 5;

    /**
     * Return options for additional info
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('-- Please Select --')],
            ['value' => self::PRICE, 'label' => __('Price')],
            ['value' => self::ADD_TO_CART, 'label' => __('Add to Cart')],
            ['value' => self::ADD_TO_WISHLIST, 'label' => __('Add to Wishlist')],
            ['value' => self::ADD_TO_COMPARE, 'label' => __('Add to Compare')],
            ['value' => self::REVIEW, 'label' => __('Review Information')],
        ];
    }
}
