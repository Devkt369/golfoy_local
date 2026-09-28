/*
 * Copyright © 2016 Magestore. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'Magento_Ui/js/lib/validation/validator'
], function (_, registry, Abstract, validator) {
    'use strict';

    return Abstract.extend({
        visibleStatus: function (status) {
            if (!parseInt(status)) {
                this.elementTmpl = 'ui/form/element/input';
                this.needValidate = true;
            } else {
                this.elementTmpl = 'ui/dynamic-rows/cells/text';
                this.needValidate = false;
            }
        },

        /**
         * Validates itself by it's validation rules using validator object.
         * If validation of a rule did not pass, writes it's message to
         * 'error' observable property.
         *
         * @returns {Object} Validate information.
         */
        validate: function () {
            if (this.needValidate) {
                var value = this.value(),
                    result = validator(this.validation, value, this.validationParams),
                    message = !this.disabled() && this.visible() ? result.message : '',
                    isValid = this.disabled() || !this.visible() || result.passed;

                if(isValid && (message === null || message === '')) {
                    var qty = registry.get(this.parentName + '.' + 'qty');
                    var qtyToSend = registry.get(this.parentName + '.' + 'qty_requested');
                    if(parseFloat(qtyToSend.value()) > parseFloat(qty.value())) {
                        isValid = false;
                        message = 'Quantity to send cannot be greater than Qty in Sending Source.';
                    }
                    if(parseFloat(qtyToSend.value()) <= 0) {
                        isValid = false;
                        message = 'Quantity must be greater than 0.';
                    }
                }

                this.error(message);
                this.error.valueHasMutated();
                this.bubble('error', message);

                //TODO: Implement proper result propagation for form
                if (this.source && !isValid) {
                    this.source.set('params.invalid', true);
                }

                return {
                    valid: isValid,
                    target: this
                };
            }
        }
    });
});
