<?php
/**
 * Copyright © Snapcommerce All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Snapcommerce\Snapmint\Block;

class Snapmint extends \Magento\Framework\View\Element\Template
{
    /**
     * @var _helper
     */
    protected $_helper;

    /**
     * @var registry
     */
    protected $registry;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $_cart;

    /**
     * Constructor
     *
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Snapcommerce\Snapmint\Helper\Data $helper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Checkout\Model\Cart $cart
     * @param Array $data
     */

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Snapcommerce\Snapmint\Helper\Data $helper,
        \Magento\Framework\Registry $registry,
        \Magento\Checkout\Model\Cart $cart,
        array $data = []
    ) {
        $this->_helper   = $helper;
        $this->_registry = $registry;
        $this->_cart = $cart;
        parent::__construct($context, $data);
    }

    // phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod

    /**
     * Arround Plugin to Skip CSRF Check
     *
     * @param \Magento\Framework\App\Request\CsrfValidator $subject
     * @param \Closure $proceed
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Framework\App\ActionInterface $action
     */

    public function _prepareLayout()
    {
        return parent::_prepareLayout();
    }

    /**
     * Get Merchant Id
     */
    public function getMerchantId()
    {
        return $this->_helper->getConfig('payment/snapmint/submerchantid');
    }

    /**
     * Get Min Order Value for which snapmint payment method will be displayed
     */
    public function getMinValue()
    {
        return $this->_helper->getConfig('payment/snapmint/min_order_total') ?: 199;
    }

    /**
     * Get Max Order Value for which snapmint payment method will be displayed
     */
    public function getMaxValue()
    {
        return $this->_helper->getConfig('payment/snapmint/max_order_total') ?: 1000000;
    }

    /**
     * Get OptionPrices for Configurable Product
     */
    public function getOptionPrices()
    {
        $Parent  = $this->getCurrentProduct();
        $options = [];

        $_children = $Parent->getTypeInstance()->getUsedProducts($Parent);
        foreach ($_children as $child) {
            $options[$child->getSku()] = $child->getPrice();

        }
        // $data    = $Parent->getTypeInstance()->getConfigurableOptions($Parent);
        // foreach ($data as $attr) {
        //     foreach ($attr as $p) {
        //         $options[$p['sku']][$p['attribute_code']] = $p['option_title'];
        //     }
        // }
        return $options;
    }

    /**
     * Get Js URL for which EMI will be displayed on PDP
     * *phpcs:disable
     */
    public function getEmiJsUrl()
    {
        $snapmint_base_url = $this->_helper->getSnapmintJSBaseUrl();

        $url = str_replace('https://', '', $snapmint_base_url) . 'assets/merchant/' . $this->getMerchantId() . '/snapmint_emi.js'; //phpcs:ignore

        return $url;
    }
    /**
     * Get Product Price on PDP
     */
    public function getProductPrice()
    {
        if ($this->getRequest()->getFullActionName() == "catalog_product_view") {

            $product = $this->_registry->registry('current_product');

            return $product->getFinalPrice();
        }
    }

    /**
     * Get Current Product Object
     */

    public function getCurrentProduct()
    {
        return $this->_registry->registry('current_product');
    }

    /**
     * Get Snapmint Success Url
     */

    public function getSnapmintSuccessUrl()
    {
        return $this->_helper->getSnapmintSuccessUrl();
    }

    /**
     * Get Snapmint Failure Url
     */

    public function getSnapmintFailureUrl()
    {
        return $this->_helper->getSnapmintFailureUrl();
    }

    /**
     * Get Magento Quote Id
     */
    public function getQuoteId()
    {
        return $this->_cart->getQuote()->getId();
    }
}
