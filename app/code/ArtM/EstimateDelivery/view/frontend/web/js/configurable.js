define([
    'jquery',
    'ArtM_EstimateDelivery/js/switcher'
], function ($, switcher) {
    'use strict';
    return function (widget) {
        $.widget('mage.configurable', widget, {
            _reloadPrice: function () {
                switcher.switch(this.options.spConfig, this.simpleProduct);
                this._super();
            }
        });

        return $.mage.configurable;
    };
});