/*
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract'
], function (registry, Element) {
    'use strict';

    return Element.extend({
        defaults: {
            skipValidation: false,
            imports: {
                update: '${ $.parentName }.repeat:value'
            }
        },

        /**
         * @param {String} value
         */
        update: function (value) {
            var date = this.getInitialValue();

            if (date && date.includes('-')) {
                const dateTimeValue =  date.split('-');
                if (0 === parseInt(value)) {
                    this.value(dateTimeValue.reverse().join("/"));
                } else {
                    this.value(dateTimeValue[2] + '/' + dateTimeValue[1]);
                }
            }
        }
    });
});
