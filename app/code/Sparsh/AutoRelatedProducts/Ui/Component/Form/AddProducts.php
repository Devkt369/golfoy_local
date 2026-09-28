<?php
/**
 * Class AddProducts
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
 * Class AddProducts
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class AddProducts implements \Magento\Framework\Data\OptionSourceInterface
{
    const RELATED = 1;
    const UPSELL = 2;
    const CROSSSELL = 3;

    /**
     * Return options for add products
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('-- Please Select --')],
            ['value' => self::RELATED, 'label' => __('Related Products')],
            ['value' => self::CROSSSELL, 'label' => __('Cross-sell Products')],
            ['value' => self::UPSELL, 'label' => __('Up-sell Products')],
        ];
    }
}
