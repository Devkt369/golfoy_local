define([
    'Amasty_Base/js/form/element/ui-promotion-fieldset',
    'uiRegistry'
], function (Fieldset) {
    'use strict';

    return Fieldset.extend({
        defaults: {
            visibleOnActions: {},
            listens: {
                '${ $.provider }:${ $.dataScope }.simple_action': 'onActionChange'
            }
        },

        /**
         * @returns {Object}
         */
        initialize: function () {
            this._super();
            this.updateVisibility(this.source?.data?.simple_action);

            return this;
        },

        /**
         * @param {string} action
         * @returns {void}
         */
        onActionChange: function (action) {
            this.updateVisibility(action);
        },

        /**
         * @param {string} action
         * @returns {void}
         */
        updateVisibility: function (action) {
            this.visible(Object.values(this.visibleOnActions).includes(action));
        }
    });
});
