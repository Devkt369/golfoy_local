/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'ko',
    'Magestore_ClickAndCollectFrontend/js/moment-timezone',
    'mage/translate',
    'underscore',
    'uiComponent',
    'uiRegistry',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/quote',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/address-converter',
    'Magento_Checkout/js/action/set-shipping-information',
    'Magestore_ClickAndCollectFrontend/js/model/pickup-locations-service',
    'Magento_Checkout/js/checkout-data',
    'mage/calendar',
    'mage/validation'
], function (
    $,
    ko,
    moment,
    $t,
    _,
    Component,
    registry,
    modal,
    quote,
    customer,
    stepNavigator,
    addressConverter,
    setShippingInformationAction,
    pickupLocationsService,
    checkoutData
) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Magestore_ClickAndCollectFrontend/store-selector',
            selectedLocationTemplate:
                'Magestore_ClickAndCollectFrontend/store-selector/selected-location',
            storeSelectorPopupTemplate:
                'Magestore_ClickAndCollectFrontend/store-selector/popup',
            storeSelectorPopupItemTemplate:
                'Magestore_ClickAndCollectFrontend/store-selector/popup-item',
            loginFormSelector:
                '#store-selector form[data-role=email-with-possible-login]',
            defaultCountryId: window.checkoutConfig.defaultCountryId,
            selectedLocation: pickupLocationsService.selectedLocation,
            quoteIsVirtual: quote.isVirtual,
            searchQuery: '',
            locationList: null,
            isLoading: pickupLocationsService.isLoading,
            selectedDate: pickupLocationsService.selectedDate,
            pickupTimeOptions: pickupLocationsService.pickupTimeOptions,
            pickupTime: pickupLocationsService.pickupTime,
            disabledDays: pickupLocationsService.disabledDays,
            disabledSpecialDate: pickupLocationsService.disabledSpecialDate,
            currentSchedule: pickupLocationsService.currentSchedule,
            currentLat: pickupLocationsService.currentLat,
            currentLng: pickupLocationsService.currentLng,
            isUseCurrentToSearch: pickupLocationsService.isUseCurrentToSearch,
            searchKey: pickupLocationsService.searchKey,
            searchResult: pickupLocationsService.searchResult,
            timeIntervalPickup: 15,
            yearSample: 2020,
            monthSample: 0,
            daySample: 1,
            popup: null,
            totalCount: null,
            curPage: null,
            numberPage: null,
            pageSize: 10,
            radius: window.checkoutConfig.click_and_collect.search_radius,
            allowPosition: pickupLocationsService.allowPosition,
            stockId: window.checkoutConfig.click_and_collect.stockId
        },

        /**
         * Init component
         *
         * @return {exports}
         */
        initialize: function () {
            this._super();
            var self = this;
            this.selectedDate.subscribe(function () {
                self.pickupTimeOptions(self.loadWorkingHourForDate(self.selectedDate()));
                self.pickupTime('');
            });
            this.currentLat.subscribe(function () {
                self.checkTofillToSearchForm();
            });
            this.currentLng.subscribe(function () {
                self.checkTofillToSearchForm();
            });
            this.isUseCurrentToSearch.subscribe(function () {
                self.checkTofillToSearchForm();
            });
            return this;
        },

        /***
         * Check to fill position to search form
         */
        checkTofillToSearchForm: function () {
            if (this.currentLat() !== null && this.currentLng() !== null && this.isUseCurrentToSearch()) {
                this.searchKey(this.currentLat() + ',' + this.currentLng());
            }
        },

        /**
         * Is display pick time or not
         *
         * @returns {*}
         */
        isDisplayPickTimeForm: function () {
            return window.checkoutConfig.click_and_collect.allow_to_choose_collect_data_and_time;
        },

        /**
         * Init component observable variables
         *
         * @return {exports}
         */
        initObservable: function () {
            return this._super().observe(['locationList']);
        },

        /**
         * validation form
         */
        validationForm: function () {
            $('#pick-up-time-form').validation();
        },

        /**
         * Init date picker
         */
        initDatePicker: function () {
            var self = this;
            var location = this.selectedLocation();
            var timezone = location['timezone'];
            var currentTime = moment().tz(timezone).format('MM/DD/YYYY');
            $("#datepicker").datepicker("destroy");
            $('#datepicker').datepicker(
                {
                    minDate: currentTime,
                    dateFormat: 'mm/dd/yy',
                    beforeShowDay: function (date) {
                        var checkIfSpecial = self.checkIfSpecialDay(date);
                        if (checkIfSpecial !== 'no_rule') {
                            return [checkIfSpecial];
                        } else {
                            return [self.isDayEnableInSchedule(date)];
                        }
                    }
                }
            );
        },

        /**
         * Have time to pick
         *
         * @param date
         * @returns {boolean}
         */
        haveTimeToPick: function (date) {
            var loadListTime = this.loadWorkingHourForDate(date);
            return !!loadListTime.length;
        },

        /**
         * Load working hour for date
         *
         * @param date
         */
        loadWorkingHourForDate: function (date) {
            var listTime = [];
            var openHourArray = [
                'sunday_open',
                'monday_open',
                'tuesday_open',
                'wednesday_open',
                'thursday_open',
                'friday_open',
                'saturday_open'
            ];
            var closeHourArray = [
                'sunday_close',
                'monday_close',
                'tuesday_close',
                'wednesday_close',
                'thursday_close',
                'friday_close',
                'saturday_close'
            ];
            var schedule = this.currentSchedule();
            if (schedule) {
                var datePick = new Date(date);
                var day = datePick.getDay();
                var openHour = schedule[openHourArray[day]];
                var closeHour = schedule[closeHourArray[day]];
                var specialDayRule = this.disabledSpecialDate();
                var newOpenCloseHour = this.filterOpenCloseBySpecialDay(date, specialDayRule, openHour, closeHour);
                listTime = this.generateTime(
                    newOpenCloseHour[0],
                    newOpenCloseHour[1],
                    this.timeIntervalPickup,
                    date
                );
            }
            return listTime;
        },

        /**
         * Filter open close by special day
         *
         * @param date
         * @param specialDayRule
         * @param openHour
         * @param closeHour
         * @returns {[*, *]}
         */
        filterOpenCloseBySpecialDay: function (date, specialDayRule, openHour, closeHour) {
            var newOpenHour = openHour;
            var newcloseHour = closeHour;
            var self = this;
            $.each(specialDayRule, function (index, value) {
                var dateCurrent = new Date(date);
                var dateFrom = new Date(value['date_from']);
                var dateTo = new Date(value['date_to']);
                dateFrom.setHours(0, 0, 0)
                dateTo.setHours(0, 0, 0)
                dateCurrent.setHours(0, 0, 0);
                if (value['repeat']) {
                    dateFrom.setFullYear(self.yearSample);
                    dateTo.setFullYear(self.yearSample);
                    dateCurrent.setFullYear(self.yearSample);
                }

                if (dateCurrent.getTime() >= dateFrom.getTime()
                    && dateCurrent.getTime() <= dateTo.getTime()) {
                    if (value['store_status']) {
                        newOpenHour = value.time_open;
                        newcloseHour = value.time_close;
                    }
                }
            });
            return [newOpenHour, newcloseHour];
        },

        /**
         * Generate time by interval
         *
         * @param minTime
         * @param maxTime
         * @param interval
         * @param datePick
         * @returns {[]}
         */
        generateTime: function (minTime, maxTime, interval, datePick) {
            var listTime = [];
            var timeMinArray = minTime.split(":");
            var timeMinHour = timeMinArray[0];
            var timeMinMinutes = timeMinArray[1];

            var timeMinSample = this.createDateTimeWithHourMin(timeMinHour, timeMinMinutes);

            var maxTimeArray = maxTime.split(":");
            var maxTimeHour = maxTimeArray[0];
            var maxTimeMinutes = maxTimeArray[1];

            var timeMaxSample = this.createDateTimeWithHourMin(maxTimeHour, maxTimeMinutes);

            var timeMinStamp = timeMinSample.getTime();
            var timeMaxStamp = timeMaxSample.getTime();
            var timeCurrentSample = this.createSampleMinHourForCurrent();

            var indexTimeStamp = timeMinStamp;

            while (indexTimeStamp <= timeMaxStamp) {
                if (indexTimeStamp >= timeMinStamp) {
                    if (this.isPickToDay(datePick)) {
                        if (indexTimeStamp >= timeCurrentSample) {
                            listTime = this.pushTimeToList(indexTimeStamp, listTime);
                        }
                    } else {
                        listTime = this.pushTimeToList(indexTimeStamp, listTime);
                    }
                }

                indexTimeStamp = indexTimeStamp + interval * 1000 * 60;
            }
            return listTime;
        },

        /**
         * Push time to list
         *
         * @param indexTimeStamp
         * @param listTime
         */
        pushTimeToList: function (indexTimeStamp, listTime) {
            var time = new Date();
            time.setTime(indexTimeStamp);
            listTime.push({
                'value': time.getHours().toString(10).padStart(2, '0') + ':'
                    + time.getMinutes().toString(10).padStart(2, '0'),
                'label': time.getHours().toString(10).padStart(2, '0') + ':'
                    + time.getMinutes().toString(10).padStart(2, '0')
            });
            return listTime;
        },

        /**
         * Is pick today
         *
         * @param datePick
         * @returns {boolean}
         */
        isPickToDay: function (datePick) {
            var location = this.selectedLocation();
            var timezone = location['timezone'];

            var currentDate = moment().tz(timezone).format('MM/DD/YYYY');

            if (currentDate === datePick) {
                return true;
            } else {
                return false;
            }
        },

        /**
         * Create sample min hour for current
         *
         * @returns {Date}
         */
        createSampleMinHourForCurrent() {
            var location = this.selectedLocation();
            var timezone = location['timezone'];
            var currentTime = moment().tz(timezone).format('HH:mm');
            if ($('#clickandcollect_time_prepare_pickup') && $('#clickandcollect_time_prepare_pickup').val()) {
                var time_prepare_pickup = $('#clickandcollect_time_prepare_pickup').val();
                currentTime = moment().tz(timezone).add(parseFloat(time_prepare_pickup), 'm').format('HH:mm');
            }
            var currentTimeArray = currentTime.split(":");
            var currentMinHour = currentTimeArray[0];
            var currentMinMinutes = currentTimeArray[1];
            return this.createDateTimeWithHourMin(currentMinHour, currentMinMinutes);
        },

        /**
         * Create date time with hour min
         *
         * @param hour
         * @param min
         * @returns {Date}
         */
        createDateTimeWithHourMin(hour, min) {
            var dateTime = new Date();
            dateTime.setFullYear(this.yearSample, this.monthSample, this.daySample);
            dateTime.setHours(hour);
            dateTime.setMinutes(min);
            dateTime.setSeconds(0);
            return dateTime;
        },

        /**
         * Is day disable in schedule
         *
         * @param date
         * @returns {boolean}
         */
        isDayEnableInSchedule: function (date) {
            var isHaveTime = this.haveTimeToPick(date);
            return ($.inArray(date.getDay(), this.disabledDays()) === -1) && isHaveTime;
        },

        /**
         * Is day disable in schedule
         *
         * @param date
         * @returns {boolean}
         */
        checkIfSpecialDay: function (date) {
            var specialDayRule = this.disabledSpecialDate();
            var isEnabled = 'no_rule';
            var self = this;
            $.each(specialDayRule, function (index, value) {
                var dateCurrent = new Date(date);
                var dateFrom = new Date(value['date_from']);
                var dateTo = new Date(value['date_to']);
                dateFrom.setHours(0, 0, 0)
                dateTo.setHours(0, 0, 0)
                dateCurrent.setHours(0, 0, 0);
                if (value['repeat']) {
                    dateFrom.setFullYear(self.yearSample);
                    dateTo.setFullYear(self.yearSample);
                    dateCurrent.setFullYear(self.yearSample);
                }

                if (dateCurrent.getTime() >= dateFrom.getTime()
                    && dateCurrent.getTime() <= dateTo.getTime()) {
                    if (!value['store_status']) {
                        isEnabled = false;
                    } else {
                        isEnabled = true;
                    }
                }
            });

            return isEnabled;
        },

        /**
         * Set shipping information handler
         */
        setPickupInformation: function () {
            pickupLocationsService.updateShippingAddressWithTimePick(this.selectedLocation());
            var shippingAddress = quote.shippingAddress();
            var self = this;

            if (this.validatePickupInformation()) {
                shippingAddress = addressConverter.quoteAddressToFormAddressData(shippingAddress);
                checkoutData.setShippingAddressFromData(shippingAddress);
                setShippingInformationAction().done(function () {
                    stepNavigator.next();
                    self.displayBillingForm();
                });
            }
        },

        /**
         * Display billing form instead of use the same
         */
        displayBillingForm: function () {
            var billingBlock = $(".billing-address-same-as-shipping-block");
            $.each(billingBlock, function (index, value) {
                try {
                    var viewModelBilling = ko.dataFor(value);
                    if (!viewModelBilling.isAddressSameAsShipping() && viewModelBilling.isAddressDetailsVisible()) {
                        viewModelBilling.editAddress();
                    }
                } catch (exception) {
                    console.log(exception);
                }
            });
        },

        /**
         * @return {*}
         */
        getPopup: function () {
            var self = this;
            if (!this.popup) {
                this.popup = modal(
                    this.popUpList.options,
                    $(this.popUpList.element)
                );
                $('.modal-popup .modal-content').scroll(function () {
                    if (($(this).scrollTop() + $(this).innerHeight() >= $(this)[0].scrollHeight)
                        && self.curPage < self.numberPage && !self.searchResult()) {
                        self.loadMore(self.curPage + 1);
                    }
                });
            }

            return this.popup;
        },

        /**
         * Load more
         *
         * @param pageToLoad
         */
        loadMore: function (pageToLoad) {
            this.getAllLocations(pageToLoad);
        },

        /**
         * @returns void
         */
        openPopup: function () {
            this.displayListLocation();
        },

        /**
         * Get current position by browser service
         */
        getCurrentPosition: function () {
            var self = this;
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (position) {
                    if (position) {
                        if (position.coords.latitude && position.coords.longitude) {
                            self.currentLat(position.coords.latitude);
                            self.currentLng(position.coords.longitude);
                            self.isUseCurrentToSearch(true);
                            self.allowPosition(true);
                        }
                    }
                }, function (error) {
                    self.allowPosition(false);
                });
            }
        },

        /**
         * Get position image url
         *
         * @returns {*}
         */
        getPositionImageSrc: function () {
            return window.checkoutConfig.click_and_collect.position_image_url;
        },

        /**
         * Display list location
         */
        displayListLocation: function () {
            this.resetPopup();
            this.getAllLocations();
            this.getPopup().openModal();
        },

        /**
         * Reset popup
         */
        resetPopup: function () {
            this.locationList([]);
            this.searchResult(false);
            this.currentLat(null);
            this.currentLng(null);
            this.isUseCurrentToSearch(false);
            this.searchKey('');
            this.totalCount = null;
            this.curPage = 1;
            this.pickupTime('');
            this.selectedDate(null);
        },

        /**
         *
         */
        searchLocation: function () {
            var self = this;
            var searchKey;
            if (!self.currentLat() && !self.currentLng()) {
                searchKey = this.searchKey();
            } else {
                searchKey = self.currentLat() + ',' + self.currentLng();
            }

            pickupLocationsService.isLoading(true);
            pickupLocationsService.searchStore(searchKey, this.radius * 1000, this.stockId).then(function (result) {
                pickupLocationsService.isLoading(false);
                self.locationList(result['data']['sortedLocationByDistance']['items']);
                self.searchResult(true);
            }).catch(function () {
                self.locationList([]);
                self.searchResult(true);
                pickupLocationsService.isLoading(false);
            });
        },

        /**
         * @param {Object} location
         * @returns void
         */
        selectPickupLocation: function (location) {
            pickupLocationsService.selectForShipping(location);
            this.getPopup().closeModal();
        },

        /**
         * @param {Object} location
         * @returns {*|Boolean}
         */
        isPickupLocationSelected: function (location) {
            return _.isEqual(this.selectedLocation(), location);
        },


        /**
         * Get distance by km
         *
         * @param distance
         * @returns {*}
         */
        getDistance: function (distance) {
            return (distance / 1000).toFixed(3) + $t(' km (Driving)');
        },

        /**
         * @returns {Boolean}
         */
        validatePickupInformation: function () {
            var emailValidationResult,
                loginFormSelector = this.loginFormSelector;

            if (!customer.isLoggedIn()) {
                $(loginFormSelector).validation();
                emailValidationResult = $(loginFormSelector + ' input[name=username]').valid() ? true : false;

                if (!emailValidationResult) {
                    $(this.loginFormSelector + ' input[name=username]').focus();

                    return false;
                }
            }

            if ($('#pick-up-time-form').validation() && !$('#pick-up-time-form').validation('isValid')) {
                return false;
            }
            if ($('#pick-up-time') && $('#pick-up-time').val()) {
                if ($('#clickandcollect_time_prepare_pickup') && $('#clickandcollect_time_prepare_pickup').val()) {
                    var shipping_time = $('#pick-up-time').val();
                    var shipping_date = $('#datepicker').val();
                    var shipping_date_time = new Date(shipping_date + ' ' + shipping_time);
                    var time_prepare_pickup = $('#clickandcollect_time_prepare_pickup').val();
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
            }
            return true;
        },

        /**
         * @returns {*}
         */
        getAllLocations: function (curPage = 1) {
            var self = this;
            pickupLocationsService.isLoading(true);
            return pickupLocationsService.getAllLocations(curPage, self.pageSize, self.stockId)
                .then(function (result) {
                    pickupLocationsService.isLoading(false);
                    if (!self.locationList()) {
                        self.locationList(result['data']['locations']['items']);
                    } else {
                        let oldLocationList = self.locationList();
                        let newLocationList = oldLocationList.concat(result['data']['locations']['items']);
                        self.locationList(newLocationList);
                    }
                    self.totalCount = result['data']['locations']['total_count'];
                    self.curPage = curPage;
                    self.numberPage = Math.floor(self.totalCount / self.pageSize) + 1;
                }).catch(function () {
                    pickupLocationsService.isLoading(false);
                });
        }
    });
});
