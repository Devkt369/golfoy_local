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
    'mage/template',
    'jquery/ui',
], function($, mageTemplate) {

    $.widget('magestore.regionUpdater', {
        options: {
            regionJson: {},
            regionTemplate: '<option value="<%- data.value %>" title="<%- data.title %>" <% if (data.isSelected) { %>selected="selected"<% } %>>' +
            '<%- data.title %>' +
            '</option>',
            currentRegion: null,
            regionListId: '',
            regionInputId: ''
        },

        _create: function () {
            var options = this.options;
            this._initCountryElement();

            this.currentRegionOption = options.currentRegion;
            this.regionTmpl = mageTemplate(options.regionTemplate);

            this._updateRegion(this.element.find('option:selected').val());

            $(options.regionListId).on('change', $.proxy(function (e) {
                this.setOption = false;
                this.currentRegionOption = $(e.target).val();
            }, this));

            $(options.regionInputId).on('focusout', $.proxy(function () {
                this.setOption = true;
            }, this));
        },

        /**
         * init country element
         * @private
         */
        _initCountryElement: function() {
            this.element.on('change', $.proxy(function (e) {
                this._updateRegion($(e.target).val());
            }, this));
        },

        /**
         * Remove options from dropdown list
         * @param {Object} selectElement - jQuery object for dropdown list
         * @private
         */
        _removeSelectOptions: function (selectElement) {
            selectElement.find('option').each(function (index) {
                if (index) {
                    $(this).remove();
                }
            });
        },

        /**
         * Render dropdown list
         * @param {Object} selectElement - jQuery object for dropdown list
         * @param {String} key - region code
         * @param {Object} value - region object
         * @private
         */
        _renderSelectOption: function (selectElement, key, value) {
            selectElement.append($.proxy(function () {
                var name = value.name.replace(/[!"#$%&'()*+,.\/:;<=>?@[\\\]^`{|}~]/g, '\\$&'),
                    tmplData,
                    tmpl;

                if (value.code && $(name).is('span')) {
                    key = value.code;
                    value.name = $(name).text();
                }

                tmplData = {
                    value: key,
                    title: value.name,
                    isSelected: false
                };

                if (this.options.defaultRegion === key) {
                    tmplData.isSelected = true;
                }

                tmpl = this.regionTmpl({
                    data: tmplData
                });

                return $(tmpl);
            }, this));
        },

        /**
         * Update dropdown list based on the country selected
         * @param {String} country - 2 uppercase letter for country code
         * @private
         */
        _updateRegion: function (country) {
            // Clear validation error messages
            var options = this.options,
                regionList = $(options.regionListId),
                regionInput = $(options.regionInputId),
                container = regionList.parents('div.field');

            this._checkRegionRequired(country);
            regionInput.val('');

            // Populate state/province dropdown list if available or use input box
            if (options.regionJson[country]) {
                this._removeSelectOptions(regionList);
                $.each(options.regionJson[country], $.proxy(function (key, value) {
                    this._renderSelectOption(regionList, key, value);
                }, this));

                if (this.currentRegionOption) {
                    regionList.val(this.currentRegionOption);
                }

                if (this.setOption) {
                    regionList.find('option').filter(function () {
                        return this.text === regionInput.val();
                    }).prop('selected', true);
                }

                if (this.options.isRegionRequired) {
                    regionList.addClass('required-entry').removeAttr('disabled');
                    container.addClass('required').show();
                } else {
                    regionList.removeClass('required-entry validate-select').removeAttr('data-validate');
                    container.removeClass('required');
                    regionList.show();
                }

                if(regionList.find('option:selected').length == 0) {
                    regionList.find('option').first().prop('selected', true);
                }

                regionList.prop('disabled', false).show();
                regionInput.prop('disabled', true).hide();
            } else {
                this._removeSelectOptions(regionList);

                if (this.options.isRegionRequired) {
                    regionInput.addClass('required-entry').removeAttr('disabled');
                    container.addClass('required').show();
                } else {
                    container.removeClass('required');
                    regionInput.removeClass('required-entry');
                }

                regionList.prop('disabled', true).hide();
                regionInput.prop('disabled', false).show();
            }

            // Add defaultvalue attribute to state/province select element
            regionList.attr('defaultvalue', this.options.defaultRegion);
        },

        /**
         * Check if the selected country has a mandatory region selection
         *
         * @param {String} country - Code of the country - 2 uppercase letter for country code
         * @private
         */
        _checkRegionRequired: function (country) {
            var self = this;

            this.options.isRegionRequired = false;
            $.each(this.options.regionJson.config['regions_required'], function (index, elem) {
                if (elem === country) {
                    self.options.isRegionRequired = true;
                }
            });
        }
    });

    return $.magestore.regionUpdater;
});
