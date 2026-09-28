define([
    'jquery',
    'ArtM_EstimateDelivery/js/switcher'
], function ($, switcher) {
    'use strict';
    return function (widget) {
        $.widget('mage.SwatchRenderer', widget, {
            _UpdatePrice: function () {
                switcher.switch(this.options.jsonConfig, this.getProduct());
                this._super();
            }
        });
        return $.mage.SwatchRenderer;
    };  
});