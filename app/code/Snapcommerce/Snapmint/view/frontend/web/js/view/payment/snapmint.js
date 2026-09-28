define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'snapmint',
                component: 'Snapcommerce_Snapmint/js/view/payment/method-renderer/snapmint-method'
            }
        );
        return Component.extend({});
    }
);