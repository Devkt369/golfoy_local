var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/quote': {
                'Magestore_ClickAndCollectFrontend/js/model/quote-ext': true
            },
            'Magento_Checkout/js/view/shipping-information': {
                'Magestore_ClickAndCollectFrontend/js/view/shipping-information-ext': true
            },
            'Magento_Checkout/js/view/form/element/email': {
                'Magestore_ClickAndCollectFrontend/js/view/form/element/email-ext': true
            }
        }
    }
};
