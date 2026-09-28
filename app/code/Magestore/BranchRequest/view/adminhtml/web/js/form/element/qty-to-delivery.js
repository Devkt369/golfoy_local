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
        /**
         * Validates itself by it's validation rules using validator object.
         * If validation of a rule did not pass, writes it's message to
         * 'error' observable property.
         *
         * @returns {Object} Validate information.
         */
        validate: function () {
            var deliveryIndex = 'os_branchrequest_edit_form.areas.transfer_summary.transfer_summary.delivery_modal';
            var deliveryModal = registry.get(deliveryIndex);
            if (this.visible() && deliveryModal && deliveryModal.state()) {
                var value = this.value(),
                    result = validator(this.validation, value, this.validationParams),
                    message = !this.disabled() && this.visible() ? result.message : '',
                    isValid = this.disabled() || !this.visible() || result.passed;

                if (isValid && (message === null || message === '')) {
                    var qtyRequested = registry.get(this.parentName + '.' + 'qty_requested');
                    var qtyDelivered = registry.get(this.parentName + '.' + 'qty_delivered');
                    var qtyToDelivery = registry.get(this.parentName + '.' + 'qty_to_delivery');
                    var qtyNeedtoDelivery = parseFloat(qtyRequested.value()) - parseFloat(qtyDelivered.value());
                    if (qtyNeedtoDelivery < parseFloat(qtyToDelivery.value())) {
                        isValid = false;
                        message = 'Quantity to send cannot be greater than available qty to send';
                    }
                    if (parseFloat(qtyToDelivery.value()) <= 0) {
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
