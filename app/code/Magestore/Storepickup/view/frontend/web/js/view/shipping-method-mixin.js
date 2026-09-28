define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    'uiRegistry',
    'mage/translate',
    'Magento_Checkout/js/model/shipping-rate-service'
], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressList,
    addressConverter,
    quote,
    createShippingAddress,
    selectShippingAddress,
    shippingRatesValidator,
    formPopUpState,
    shippingService,
    selectShippingMethodAction,
    rateRegistry,
    setShippingInformationAction,
    stepNavigator,
    modal,
    checkoutDataResolver,
    checkoutData,
    registry,
    $t
) {
    'use strict';

    return function (Component) {
        return Component.extend({
            validateShippingInformation: function () {
                var result = this._super();
                if ($('#shipping_time') && $('#shipping_time').val()) {
                    var shipping_time = $('#shipping_time').val();
                    var shipping_date = $('#shipping_date').val();
                    var shipping_date_time = new Date(shipping_date + ' ' + shipping_time);
                    var time_prepare_pickup = $('#time_prepare_pickup').val();
                    var utcStr = new Date().toUTCString();
                    let newDate = new Date(utcStr);
                    var date_time_to_prepare = newDate.getTime() + parseFloat(time_prepare_pickup) * 60000;
                    if (date_time_to_prepare > shipping_date_time.getTime()) {
                        var messageContainer = registry.get('checkout.errors').messageContainer;
                        messageContainer.addErrorMessage({
                            message: $t('The selected pickup time is not available. Please refresh page and select again!')
                        });

                        return false;
                    }
                }

                return result;
            }
        });
    }
});
