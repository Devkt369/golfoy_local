define([
    'jquery',
    'Magento_Ui/js/form/element/select',
    'uiRegistry'
], function ($, Select, uiRegistry) {
    'use strict';

    return Select.extend({
        UNSELECTED_VALUE: 0,
        POSITION_NAMES: ["sales_rule_form.sales_rule_form.promo_banners_highlights.countdown_timer.timer_position",
            "salesrulestaging_update_form.salesrulestaging_update_form.promo_banners_highlights.countdown_timer.timer_position"],

        /**
         * @returns {void}
         */
        initialize: function () {
            this._super();
            this.getParentComponent().elems.subscribe(function () {
                this.updateDependedFieldsVisibility();
            }.bind(this));
        },

        /**
         * @returns {void}
         */
        onUpdate: function () {
            this.updateDependedFieldsVisibility();
            this._super();
        },

        /**
         * @return {Object}
         */
        getParentComponent: function () {
            return uiRegistry.get(this.parentName);
        },

        /**
         * Toggle visibility of dependent fields
         * @returns {void}
         */
        updateDependedFieldsVisibility: function () {
            if (this.getParentComponent()) {
                this.getParentComponent().elems().forEach(function (element) {
                    if ($.inArray(element.name, this.POSITION_NAMES) >= 0) {
                        element.visible(+this.value() !== this.UNSELECTED_VALUE)
                    }
                }.bind(this));
            }
        }
    });
});
