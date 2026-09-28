/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiComponent',
    'underscore',
    'jquery',
    'knockout',
    'uiRegistry',
    'Magento_Checkout/js/model/quote',
    'Magestore_ClickAndCollectFrontend/js/map/map-loader-checkout',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/shipping-rate-service',
    'Magestore_ClickAndCollectFrontend/js/model/shipping-rate-processor/store-pickup-address',
    'Magestore_ClickAndCollectFrontend/js/model/pickup-locations-service'
], function (
    Component,
    _,
    $,
    ko,
    registry,
    quote,
    mapLoader,
    selectShippingMethodAction,
    checkoutData,
    shippingService,
    stepNavigator,
    shippingRateService,
    shippingRateProcessor,
    pickupLocationsService
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magestore_ClickAndCollectFrontend/store-pickup',
            deliveryMethodSelectorTemplate: 'Magestore_ClickAndCollectFrontend/delivery-method-selector',
            isVisible: false,
            isAvailable: false,
            isClickAndCollectSelected: false,
            rate: {
                'carrier_code': 'clickandcollect',
                'method_code': 'clickandcollect'
            },
            defaultCountry: window.checkoutConfig.defaultCountryId,
            rates: shippingService.getShippingRates(),
            inStoreMethod: null,
            currentLat: pickupLocationsService.currentLat,
            currentLng: pickupLocationsService.currentLng,
            isUseCurrentToSearch: pickupLocationsService.isUseCurrentToSearch,
            searchKey: pickupLocationsService.searchKey,
        },

        /**
         * @inheritdoc
         */
        initialize: function () {
            this._super();

            mapLoader.fail(function() {
                console.error("ERROR: Google maps library failed to load");
            });

            shippingRateService.registerProcessor('click-and-collect-address', shippingRateProcessor);

            quote.shippingAddress.subscribe(function (shippingAddress) {
                this.convertAddressType(shippingAddress);
            }, this);
            this.convertAddressType(quote.shippingAddress());
            this.syncWithShipping();
        },

        /**
         * Init google auto complete
         */
        initSuggest: function () {
            var self = this;
            var input= $('#search-store-input')[0];
            var autocomplete = new google.maps.places.Autocomplete(input, {});
            autocomplete.addListener("place_changed", () => {
                var place = autocomplete.getPlace();
                if (place.geometry && place.geometry.location && place.geometry.location.lat() !== 'undefined'
                    && place.geometry.location.lng() !== 'undefined') {
                    self.isUseCurrentToSearch(false);
                    self.currentLat(place.geometry.location.lat());
                    self.currentLng(place.geometry.location.lng());
                }
            });
        },

        /**
         * Handle press search key when enter
         * @param data
         * @param event
         */
        handlePressSearchKey: function (data, event) {
            data.isUseCurrentToSearch(false);
            data.currentLng(null);
            data.currentLat(null);
            /* Press enter */
            if (event.which === 13) {
                data.searchKey($('#search-store-input').val());
                data.searchLocation();
            }
            return true;
        },

        /**
         * Init component observable variables
         *
         * @return {exports}
         */
        initObservable: function () {
            this._super().observe(['isVisible']);

            this.isClickAndCollectSelected = ko.pureComputed(function () {
                return _.isMatch(quote.shippingMethod(), this.rate);
            }, this);

            this.isAvailable = ko.pureComputed(function () {
                return _.findWhere(this.rates(), {
                    'carrier_code': this.rate['carrier_code'],
                    'method_code': this.rate['method_code']
                });
            }, this);

            return this;
        },

        /**
         * Synchronize store pickup visibility with shipping step.
         *
         * @returns void
         */
        syncWithShipping: function () {
            var shippingStep = _.findWhere(stepNavigator.steps(), {
                code: 'shipping'
            });

            shippingStep.isVisible.subscribe(function (isShippingVisible) {
                this.isVisible(this.isAvailable && isShippingVisible);
            }, this);
            this.isVisible(this.isAvailable && shippingStep.isVisible());
        },

        /**
         * @returns void
         */
        selectShipping: function () {
            var nonPickupShippingMethod = _.find(
                this.rates(),
                function (rate) {
                    return (
                        rate['carrier_code'] !== this.rate['carrier_code'] &&
                        rate['method_code'] !== this.rate['method_code']
                    );
                },
                this
            );

            this.selectShippingMethod(nonPickupShippingMethod);

            registry.async('checkoutProvider')(function (checkoutProvider) {
                checkoutProvider.set(
                    'shippingAddress',
                    quote.shippingAddress()
                );
                checkoutProvider.trigger('data.reset');
            });
        },

        /**
         * @returns void
         */
        selectStorePickup: function () {
            var pickupShippingMethod = _.findWhere(
                this.rates(),
                {
                    'carrier_code': this.rate['carrier_code'],
                    'method_code': this.rate['method_code']
                },
                this
            );
            this.selectShippingMethod(pickupShippingMethod);
        },

        /**
         * @param {Object} shippingMethod
         */
        selectShippingMethod: function (shippingMethod) {
            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingAddress(
                quote.shippingAddress().getKey()
            );
        },

        /**
         * @param {Object} shippingAddress
         * @returns void
         */
        convertAddressType: function (shippingAddress) {
            if (
                !this.isClickAndCollectAddress(shippingAddress) &&
                this.isClickAndCollectSelected()
            ) {
                quote.shippingAddress(
                    $.extend({}, shippingAddress, {
                        /**
                         * Is address can be used for billing
                         *
                         * @return {Boolean}
                         */
                        canUseForBilling: function () {
                            return false;
                        },

                        /**
                         * Returns address type
                         *
                         * @return {String}
                         */
                        getType: function () {
                            return 'click-and-collect-address';
                        }
                    })
                );
            }
        },

        /**
         * @param {Object} address
         * @returns {Boolean}
         */
        isClickAndCollectAddress: function (address) {
            return address.getType() === 'click-and-collect-address';
        }
    });
});
