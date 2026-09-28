<?php
/**
 * Class DisplayModes
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
 * Class DisplayModes
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class DisplayModes implements \Magento\Framework\Data\OptionSourceInterface
{
    const BLOCK = 0;
    const AJAX = 1;

    /**
     * Return options for display modes
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::BLOCK, 'label' => __('Block')],
            ['value' => self::AJAX, 'label' => __('Ajax')],
        ];
    }
}
