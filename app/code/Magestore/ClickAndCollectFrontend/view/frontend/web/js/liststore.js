/**
 * Magestore
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Magestore
 * @package     Magestore_ClickAndCollectFrontend
 * @copyright   Copyright (c) Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
define([
    'jquery',
    'mage/template',
    'mage/url',
    'jquery/ui'
], function($, mageTemplate, url) {
    $.widget('magestore.liststore', {
        options: {
            storeTemplate: '#store-template',
            defaultStoreIcon: '',
            mediaUrlImage: '',
            storageUnit: {
                M: {
                    label: 'M',
                    factor: 1
                },
                Km: {
                    label: 'Km',
                    factor: 1000
                },
                Mi: {
                    label: 'Mi',
                    factor: 1609.34
                }
            },
            weekdayMapping: {
                0: 'sunday',
                1: 'monday',
                2: 'tuesday',
                3: 'wednesday',
                4: 'thursday',
                5: 'friday',
                6: 'saturday'
            }
        },
        _create: function () {
            var self = this, options = this.options;

            $.extend(this,{
                stores: [],
                $directionBox: $('.option-direction'),
            });

            self.storeTmpl = mageTemplate($(options.storeTemplate).html());
        },

        addStore: function (store) {
            var self = this, options = this.options;
            $(self.element).append(self.toStoreHtml(store));
            $(self.element).trigger('addStore', store);
        },

        toStoreHtml: function (store) {
            var self = this, options = this.options;
            if(store.distance) {
                var distance = parseFloat(store.distance)/options.storageUnit[options.distanceUnit].factor;
                store.distanceText = distance.toFixed(1) + ' ' + options.distanceUnit;
            }
            store.address = this.getFullAddress(store);
            store.todayStatusInfo = this.getTodayStatusInfo(store);
            store.scheduleInfo = this.getScheduleInfo(store);
            store.viewDetailUrl = url.build('clickandcollect/store/view/id/' + store.location_id);
            return $(self.storeTmpl({data: store}).trim());
        },

        /**
         * Get Schedule Info
         *
         * @param store
         * @returns {null|[]}
         */
        getScheduleInfo: function (store) {
            let schedule = store.schedule;
            if (!schedule) {
                return null;
            }
            let scheduleInfo = [];
            let weekdayArray = [
                'monday',
                'tuesday',
                'wednesday',
                'thursday',
                'friday',
                'saturday',
                'sunday'
            ];
            weekdayArray.forEach(day => {
                let dayInfo = {
                    label: $.mage.__(day[0].toUpperCase() + day.substring(1, 3)),
                    workingHour: $.mage.__('Closed')
                };
                let status = schedule[day + '_status'];
                if (status) {
                    dayInfo.workingHour = schedule[day + '_open'] + ' - ' + schedule[day + '_close'];
                }
                scheduleInfo.push(dayInfo);
            });
            return scheduleInfo;
        },

        /**
         * Get Today Status Info
         *
         * @param store
         * @returns {string|*}
         */
        getTodayStatusInfo: function (store) {
            if (!store.schedule) {
                return '';
            }
            let today = new Date();
            let weekday = this.options.weekdayMapping[today.getDay()];
            let status = store.schedule[weekday + '_status'];
            if (!status) {
                return $.mage.__('Close today');
            }
            let open = store.schedule[weekday + '_open'];
            let close = store.schedule[weekday + '_close'];
            return $.mage.__('Open today from %open - %close')
                .replace('%open', open)
                .replace('%close', close);
        },

        /**
         * Get Full Address
         * @param store
         * @returns {string}
         */
        getFullAddress: function(store) {
            let addressArr = [];
            let street = "";
            if (store.street) {
                street = store.street;
                addressArr.push(street);
            }
            let city = "";
            if (store.city) {
                city = store.city;
                addressArr.push(city);
            }
            let region = "";
            if (store.region) {
                region = store.region;
                addressArr.push(region);
            }
            let postCode = "";
            if (store.postcode) {
                postCode = store.postcode;
                addressArr.push(postCode);
            }
            let country = "";
            if (store.country_id) {
                country = store.country_id;
                addressArr.push(country);
            }
            return addressArr.join(", ");
        },

        updateListStoresEvent: function () {
            var self = this, options = this.options;
            $(self.element).find('.store-item').click(function () {
                if (!$(this).hasClass('store-active')) {
                    $(self.element).find('.store-item.store-active').removeClass('store-active');
                    $(this).addClass('store-active');
                }
                $(self.element).trigger('clickStore', {index: $(this).data('store-index')});
            });

            $(self.element).find('.arrow-icon').click(function () {
                let $store = $(this).parents('.store-item');
                $store.find('.clickandcollect-today-status').toggleClass('hide');
                $store.find('.schedule-container').toggleClass('show');
                $store.find('.arrow-icon .glyphicon').toggleClass('glyphicon-menu-up');
            });

            $(self.element).find('.btn-link.direction').click(function () {
                var $store = $(this).parents('.store-item');
                self.$directionBox.direction('setAddress', $store.data('address'));
                if($store.find('.option-direction').length) {
                    if(self.$directionBox.css('display') === "none") {
                        self.$directionBox.show();
                    } else {
                        self.$directionBox.hide();
                    }
                } else {
                    $store.append(self.$directionBox);
                    self.$directionBox.find('.directions-panel').html('');
                    self.$directionBox.show();
                }
            });

            $(self.element).find('.btn-link.street-view').click(function () {
                var $store = $(this).parents('.store-item');
                $(self.element).trigger('streetview', {index: $store.data('store-index')});
            });
        },

        addEventDirectionBox: function (storeItem) {
            var self = this, options = this.options;
            storeItem.find('.btn-link.direction').click(function () {
                var $store = $(this).parents('.store-item');
                self.$directionBox.direction('setAddress', $store.data('address'));
                if($store.find('.option-direction').length) {
                    if(self.$directionBox.css('display') === "none") {
                        self.$directionBox.show();
                    } else {
                        self.$directionBox.hide();
                    }
                } else {
                    $store.append(self.$directionBox);
                    self.$directionBox.find('.directions-panel').html('');
                    self.$directionBox.show();
                }
            });

            storeItem.find('.btn-link.street-view').click(function () {
                var $store = $(this).parents('.store-item');
                $(self.element).trigger('streetview', {index: $store.data('store-index')});
            });
        },

        getDirectionBox: function () {
            return this.$directionBox;
        },

        updateStores: function (storesjson) {
            var self = this, options = this.options;
            $(self.element).html('');

            $.each(storesjson, function (index, store) {
                store.index = index;
                self.addStore(store);
            });

            self.updateListStoresEvent();

            this.stores = storesjson;
        }
    });

    return $.magestore.liststore;
});
