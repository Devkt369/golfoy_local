/*
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'Magento_Ui/js/form/components/button',
    'uiRegistry'
], function ($, Component, registry) {
    'use strict';

    return Component.extend({
        defaults: {
            elementTmpl: 'Magestore_ClickAndCollectAdminUi/element/apply-to-all'
        },

        handleOnclick: function () {
            var mondayStatusField = registry.get('index = monday_status'),
                mondayOpeningHourField = registry.get('index = monday_open_hour'),
                mondayOpeningMinField = registry.get('index = monday_open_min'),
                mondayClosingHourField = registry.get('index = monday_close_hour'),
                mondayClosingMinField = registry.get('index = monday_close_min')

            this.scheduleFieldKeys().forEach(fields => {
                let statusField = registry.get('index = ' + fields.status),
                    openingHourField = registry.get('index = ' + fields.opening_hour),
                    openingMinField = registry.get('index = ' + fields.opening_min),
                    closingHourField = registry.get('index = ' + fields.closing_hour),
                    closingMinField = registry.get('index = ' + fields.closing_min)

                statusField.value(mondayStatusField.value());
                openingHourField.value(mondayOpeningHourField.value());
                openingMinField.value(mondayOpeningMinField.value());
                closingHourField.value(mondayClosingHourField.value());
                closingMinField.value(mondayClosingMinField.value());
            });
        },

        scheduleFieldKeys: function() {
            var fields = [];

            ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'].forEach(day => {
                fields.push({
                    'status': day + '_status',
                    'opening_hour': day + '_open_hour',
                    'opening_min': day + '_open_min',
                    'closing_hour': day + '_close_hour',
                    'closing_min': day + '_close_min'
                });
            });
            return fields;
        }
    });
});
