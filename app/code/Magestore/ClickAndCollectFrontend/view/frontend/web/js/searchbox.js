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
 * @package     Magestore_Storepickup
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */
define([
    'jquery',
    'jquery/ui',
    // 'jquery/ui-modules/widgets/slider'
    'Magestore_ClickAndCollectFrontend/js/slider'
], function($) {
    $.widget('magestore.searchbox', {
        options: {
            defaultRadius: 10,
            distanceUnit: 'Km',
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
        },
        _create: function () {
            var self = this, options = this.options;

            $.extend(this, {
                $sliderBar:  $(self.element).find('.slider-range-bar'),
                $sliderAmount: $(self.element).find('.slider-range-amount'),
                $inputAddress: $(self.element).find('.input-search-distance'),
                $btnSearchDistance: $(self.element).find('.btn-search-distance'),
                $btnResetSearchDistance: $(self.element).find('.btn-reset-search-distance'),
            });

            self.$sliderBar.slider({
                range: "min",
                min: 1,
                max: 50,
                value: options.defaultRadius,
                slide: function( event, ui ) {
                    self.$sliderAmount.html(ui.value + ' ' + options.distanceUnit);
                },
                change: function( event, ui ) {
                    self.searchDistance();
                },
            });

            self.$btnSearchDistance.click(function () {
                self.searchDistance();
            });

            self.$btnResetSearchDistance.click(function () {
                self.resetDistance();
                $(self.element).trigger('reset-map');
            });
        },

        resetDistance: function () {
            var options = this.options;
            this.$inputAddress.val('');
            this.$sliderBar.slider('value', options.defaultRadius);
            this.$sliderAmount.html(options.defaultRadius + ' ' + options.distanceUnit);
        },

        searchDistance: function () {
            var self = this, options = this.options;
            if(self.$inputAddress.val()) {
                var geocoder = new google.maps.Geocoder();
                geocoder.geocode({
                    'address': self.$inputAddress.val()
                }, function (results, status) {
                    if (status === google.maps.GeocoderStatus.OK) {
                        $(self.element).trigger('geocoded', {location: results[0].geometry.location});
                    }
                });
            }
        },

        getRadius: function () {
            var options = this.options, factor;
            if(options.storageUnit[options.distanceUnit]) {
                factor = options.storageUnit[options.distanceUnit].factor;
            } else {
                factor = options.storageUnit['Km'].factor;
            }

            return this.$sliderBar.slider('value') * factor;
        },

        getInputAddress: function () {
            return this.$inputAddress;
        },

        initInputSearchAddess: function () {
            var self = this, options = this.options;
            this.searchAutoComplete = new google.maps.places.SearchBox(this.$inputAddress[0]);
            this.geocoder = new google.maps.Geocoder();
            google.maps.event.addListener(this.searchAutoComplete, 'places_changed', function () {
                self.searchDistance()
            });
        }
    });

    return $.magestore.searchbox;
});
