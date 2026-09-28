/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    "mage/calendar"
], function ($) {
    'use strict';

    return function () {
        $('#date-range-select').change(function (element) {
            $('#from').val('');
            $('#to').val('');
            if (element.target.value === 'custom_range') {
                $('.date-range').show();
            } else {
                $('.date-range').hide();
            }
        });
        $("#from").calendar({
            dateFormat: "mm/dd/yy",
            showOn: "button",
            showAnim: "",
            changeMonth: true,
            changeYear: true,
            buttonImageOnly: null,
            buttonImage: null,
            showButtonPanel: true,
            showWeek: true,
            timeFormat: '',
            showTime: false,
            showHour: false,
            showMinute: false
        });
        $("#to").calendar({
            dateFormat: "mm/dd/yy",
            showOn: "button",
            showAnim: "",
            changeMonth: true,
            changeYear: true,
            buttonImageOnly: null,
            buttonImage: null,
            showButtonPanel: true,
            showWeek: true,
            timeFormat: '',
            showTime: false,
            showHour: false,
            showMinute: false
        });
    };
});
