/**
 * Copyright © Magestore, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'jquery',
    'moment',
    'mageUtils',
    'Magento_Ui/js/form/element/date',
], function ($, moment, utils, Date) {
    'use strict';

    return Date.extend({
        /**
         * Initializes regular properties of instance.
         *
         * @returns {Object} Chainable.
         */
        initConfig: function () {
            this._super();
            this.options.storeLocale = 'en_US';
            this.options.dateFormat = this.pickerDefaultDateFormat;

            if (!this.options.timeFormat) {
                this.options.timeFormat = this.pickerDefaultTimeFormat;
            }

            this.prepareDateTimeFormats();

            if (this.options.customDateFormat) {
                this.options.dateFormat = this.options.customDateFormat;
                this.pickerDateTimeFormat = utils.convertToMomentFormat(this.options.customDateFormat);
            }
            return this;
        },

        /**
         * Show element
         *
         * @returns {*}
         */
        show: function () {
            this.visible(true);
            if (this.dateValue) {
                var value = moment(this.dateValue, this.outputDateFormat);
                value = value.format(this.pickerDateTimeFormat);
                var selector = 'input[name="' + this.inputName + '"]'
                $(selector).val(value);
            }
            return this;
        }

    });
});
