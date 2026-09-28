<?php
/**
 * Class Conditions
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
 * Class Conditions
 *
 * @category Sparsh
 * @package  Sparsh_AutoRelatedProducts
 * @author   Sparsh <magento@sparsh-technologies.com>
 * @license  https://www.sparsh-technologies.com  Open Software License (OSL 3.0)
 * @link     https://www.sparsh-technologies.com
 */
class Conditions implements \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * Conditions constructor.
     *
     * @param \Magento\Backend\Model\Url $backendUrlManager
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Backend\Model\Url $backendUrlManager,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->backendUrlManager = $backendUrlManager;
        $this->request = $request;
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
        if ($element->getRule() && $element->getRule()->getConditions()) {
            $html = $element->getRule()->getConditions()->asHtmlRecursive();
            if ($this->request->getParam('type') == 'product') {
                $url = $this->backendUrlManager->getUrl('sparsh_auto_related_products/preview/conditionproducts');
                $html .= '<button
                type="button"
                id="sparsh_auto_related_products_conditions_preview_product_button"
                class="sparsh-auto-related-products-conditions-preview-product-button"
                data-mage-init=\'{"sparsh-auto-related-preview-products":{"url": "'.$url.'", "type": "conditions"}}\'
                data-hidelist="false">Preview Products</button>
                <div class="sparsh-auto-related-products-conditions-product-list"></div>';
            }
            return $html;
        }
        return '';
    }
}
