define([
    'Amasty_Base/js/form/element/ui-promotion-fieldset',
    'underscore',
    'uiRegistry'
], function (Fieldset, _, registry) {
    'use strict';

    return Fieldset.extend({
        defaults: {
            rulesActions: [
                //Free Gift Rules
                'ampromo_product',
                'ampromo_items',
                'ampromo_cart',
                'ampromo_spent',
                'ampromo_eachn'
            ],
            listens: {
                '${$.provider}:data.simple_action': 'onChange'
            }
        },

        /**
         * @returns {Object}
         */
        initialize: function () {
            this._super();
            registry.get('sales_rule_form.sales_rule_form.actions.simple_action', function (component) {
                this.checkVisibility(component.value());
            }.bind(this));
        },

        /**
         * @param {string} value
         * @returns {void}
         */
        onChange: function (value) {
            this.checkVisibility(value);
        },

        /**
         * @param {string} value
         * @returns {void}
         */
        checkVisibility: function (value) {
            if (_.contains(this.rulesActions, value)) {
                this.visible(true);
            } else {
                this.visible(false);
            }
        }
    });
});
