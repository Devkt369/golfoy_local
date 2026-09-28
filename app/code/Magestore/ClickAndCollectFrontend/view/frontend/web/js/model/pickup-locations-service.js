/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'knockout',
    'moment',
    'Magestore_ClickAndCollectFrontend/js/model/resource-url-manager',
    'mage/storage',
    'Magento_Customer/js/customer-data',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/select-shipping-address',
    'underscore',
    'mage/translate',
    'mage/url'
], function (
    $,
    ko,
    moment,
    resourceUrlManager,
    storage,
    customerData,
    checkoutData,
    addressConverter,
    selectShippingAddressAction,
    _,
    $t,
    url
) {
    'use strict';

    var countryData = customerData.get('directory-data');

    return {
        isLoading: ko.observable(false),
        selectedLocation: ko.observable(null),
        selectedDate: ko.observable(null),
        pickupTimeOptions: ko.observableArray([]),
        pickupTime: ko.observable(''),
        disabledDays: ko.observableArray([]),
        disabledSpecialDate: ko.observableArray([]),
        currentSchedule: ko.observable({}),
        locationList: ko.observableArray([]),
        currentLat: ko.observable(null),
        currentLng: ko.observable(null),
        isUseCurrentToSearch: ko.observable(false),
        searchKey: ko.observable(''),
        searchResult: ko.observable(false),
        allowPosition: ko.observable(true),

        /**
         * Get all pickup locations based on given search criteria.
         *
         * @see Magento/InventoryInStorePickup/Model/SearchCriteria/getAllLocationsCriteria.php
         */
        getAllLocations: function (curPage, pageSize, stockId) {
            return resourceUrlManager.getLocationList(curPage, pageSize, stockId);
        },

        /**
         * Search store by search key
         *
         * @param searchKey
         * @param radius
         * @param stockId
         * @returns {Promise<{total_count: number, total_page: number, items: T[], current_page: (*|number)}>|*}
         */
        searchStore: function (searchKey, radius, stockId) {
            return resourceUrlManager.searchStore(searchKey, radius, stockId);
        },

        /**
         * Select location for sipping.
         *
         * @param {Object} location
         * @returns void
         */
        selectForShipping: function (location) {
            this.selectedLocation(location);
            this.currentSchedule(location['schedule'])
            this.setDisableDate(location['schedule'])
            this.setDisableSpecialDay(location['special_days'])
        },

        /**
         * Update shipping address
         *
         * @param location
         */
        updateShippingAddressWithTimePick: function (location) {
            var self = this;
            var extensionAttribute = {
                'location_pickup_id': location['location_id'],
            };
            var dateSelect = self.selectedDate();
            if (dateSelect) {
                dateSelect = moment(dateSelect, 'MM/DD/YYYY').format('YYYY-MM-DD');
                if (self.pickupTime()) {
                    extensionAttribute['pickup_date_time'] = dateSelect + ' ' + self.pickupTime() + ':00';
                } else {
                    extensionAttribute['pickup_date_only'] = dateSelect
                }
            }
            var addressFormat = this.formatAddress(location);
            var address = $.extend(
                {},
                addressConverter.formAddressDataToQuoteAddress({
                    firstname: addressFormat.name,
                    lastname: 'Store',
                    street:addressFormat.street,
                    city: addressFormat.city,
                    postcode: addressFormat.postcode,
                    'country_id': addressFormat['country_id'],
                    country: addressFormat.country,
                    telephone: addressFormat.telephone,
                    'region_id': addressFormat['region_id'],
                    region: addressFormat.region,
                    'save_in_address_book': 0
                }),
                {
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
                    },
                    'extension_attributes': extensionAttribute
                }
            );
            selectShippingAddressAction(address);
            checkoutData.setSelectedShippingAddress(address.getKey());
        },

        /**
         * Set disable day
         *
         * @param schedule
         */
        setDisableDate(schedule) {
            if (schedule) {
                var dateStatusArray = [
                    'sunday_status',
                    'monday_status',
                    'tuesday_status',
                    'wednesday_status',
                    'thursday_status',
                    'friday_status',
                    'saturday_status'
                ];
                var disableDay = [];
                $.each(dateStatusArray, function (index, value) {
                    if (!schedule[value]) {
                        disableDay.push(index);
                    }
                });
                this.disabledDays(disableDay);
            }
        },

        /**
         * Set disable special days
         *
         * @param specialDays
         */
        setDisableSpecialDay(specialDays) {
            this.disabledSpecialDate(specialDays);
        },

        /**
         * Formats address returned by REST endpoint to match checkout address field naming.
         *
         * @param {Object} address - Address object returned by REST endpoint.
         */
        formatAddress: function (address) {
            var region = '';
            if (address['region_id']) {
                region = this.getRegionName(
                    address['country_id'],
                    address['region_id']
                );
            } else {
                region = address['region'];
            }
            return {
                name: address.name,
                description: address.description,
                latitude: address.latitude,
                longitude: address.longitude,
                street: [address.street],
                city: address.city,
                postcode: address.postcode,
                'country_id': address['country_id'],
                country: this.getCountryName(address['country_id']),
                telephone: address.telephone,
                'region_id': address['region_id'],
                region: region,
                'location_pickup_id': address['location_id']
            };
        },

        /**
         * Get country name by id.
         *
         * @param {*} countryId
         * @return {String}
         */
        getCountryName: function (countryId) {
            return countryData()[countryId] !== undefined ?
                countryData()[countryId].name
                : ''; //eslint-disable-line
        },

        /**
         * Returns region name based on given country and region identifiers.
         *
         * @param {String} countryId - Country identifier.
         * @param {String} regionId - Region identifier.
         */
        getRegionName: function (countryId, regionId) {
            var regions = countryData()[countryId] ?
                countryData()[countryId].regions
                : null;

            return regions && regions[regionId] ? regions[regionId].name : '';
        },

        /**
         * Process response errors.
         *
         * @param {Object} response
         * @returns void
         */
        processError: function (response) {
            var expr = /([%])\w+/g,
                error;

            if (response.status === 401) {
                //eslint-disable-line eqeqeq
                window.location.replace(url.build('customer/account/login/'));

                return;
            }

            try {
                error = JSON.parse(response.responseText);
            } catch (exception) {
                error = $t(
                    'Something went wrong with your request. Please try again later.'
                );
            }

            if (error.hasOwnProperty('parameters')) {
                error = error.message.replace(expr, function (varName) {
                    varName = varName.substr(1);

                    if (error.parameters.hasOwnProperty(varName)) {
                        return error.parameters[varName];
                    }

                    return error.parameters.shift();
                });
            }
        }
    };
});
