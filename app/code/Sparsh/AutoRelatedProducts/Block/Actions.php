<?php
/**
 * Class Actions
 *
 * PHP version 7
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
namespace Sparsh\AutoRelatedProducts\Block;

use Magento\Framework\Data\Form\Element\AbstractElement;

/**
 * Class Actions
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Actions implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * Actions constructor.
     *
     * @param \Magento\Backend\Model\Url $backendUrlManager
     */
    public function __construct(
        \Magento\Backend\Model\Url $backendUrlManager
    ) {
        $this->backendUrlManager = $backendUrlManager;
    }

    /**
     * Render element
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    public function render(AbstractElement $element)
    {
        if ($element->getRule() && $element->getRule()->getActions()) {
            $html = $element->getRule()->getActions()->asHtmlRecursive();
            $url = $this->backendUrlManager->getUrl('sparsh_auto_related_products/preview/actionproducts');
                $html .= '<button
                type="button"
                id="sparsh_auto_related_products_actions_preview_product_button"
                class="sparsh-auto-related-products-actions-preview-product-button"
                data-mage-init=\'{"sparsh-auto-related-preview-products":{"url": "'.$url.'", "type": "actions"}}\'
                data-hidelist="false">Preview Products</button>
                <div class="sparsh-auto-related-products-actions-product-list"></div>';
            return $html;
        }
        return '';
    }
}
