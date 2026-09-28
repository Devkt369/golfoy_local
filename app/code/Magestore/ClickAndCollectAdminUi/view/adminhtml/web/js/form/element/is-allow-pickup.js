/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function (Select, registry) {
    'use strict';

    return Select.extend({
        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            var scheduleFieldKey = 'schedule_id',
                scheduleId = registry.get('index = ' + scheduleFieldKey)

            if (scheduleId) {
                scheduleId.required(!!parseInt(value));
                scheduleId.validation['required-entry'] = !!parseInt(value);
            }

            return this._super();
        },

        /**
         * Sets initial value of the element and subscribes to it's changes.
         *
         * @returns {Select} Chainable.
         */
        setInitialValue: function () {
            this.initialValue = this.getInitialValue();

            if (this.value.peek() !== this.initialValue) {
                this.value(this.initialValue);
            }

            this.on('value', this.onUpdate.bind(this));
            this.isUseDefault(this.disabled());
            this.onUpdate(this.value());
            return this;
        },
    });
});
