/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/model/quote'
], function (quote) {
    'use strict';

    var storePickupShippingInformation = {
        defaults: {
            template: 'Magestore_ClickAndCollectFrontend/shipping-information'
        },

        /**
         * Get shipping method title based on delivery method.
         *
         * @return {String}
         */
        getShippingMethodTitle: function () {
            var shippingMethod = quote.shippingMethod(),
                locationName = '',
                title;

            if (!this.isClickAndCollect()) {
                return this._super();
            }

            title = shippingMethod['carrier_title'] + ' - ' + shippingMethod['method_title'];

            if (quote.shippingAddress().firstname !== undefined) {
                locationName = quote.shippingAddress().firstname + ' ' + quote.shippingAddress().lastname;
                title += ' "' + locationName + '"';
            }

            return title;
        },

        /**
         * Get is store pickup delivery method selected.
         *
         * @returns {Boolean}
         */
        isClickAndCollect: function () {
            var shippingMethod = quote.shippingMethod(),
                isClickAndCollect = false,
                isMagentoClickAndCollect = false;

            if (shippingMethod !== null) {
                isClickAndCollect = shippingMethod['carrier_code'] === 'clickandcollect' &&
                    shippingMethod['method_code'] === 'clickandcollect';

                isMagentoClickAndCollect = shippingMethod['carrier_code'] === 'instore' &&
                    shippingMethod['method_code'] === 'pickup';
            }

            return isClickAndCollect || isMagentoClickAndCollect;
        }
    };

    return function (shippingInformation) {
        return shippingInformation.extend(storePickupShippingInformation);
    };
});
